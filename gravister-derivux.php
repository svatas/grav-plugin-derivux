<?php

declare(strict_types=1);

namespace Grav\Plugin;

use Grav\Common\Plugin;
use Grav\Events\PermissionsRegisterEvent;
use Grav\Framework\Acl\PermissionsReader;
use RocketTheme\Toolbox\Event\Event;

final class GravisterDerivuxPlugin extends Plugin
{
    public static function getSubscribedEvents(): array
    {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', -1000],
            'onApiRegisterRoutes' => ['onApiRegisterRoutes', 0],
            'onApiSidebarItems' => ['onApiSidebarItems', 0],
            'onApiPluginPageInfo' => ['onApiPluginPageInfo', 0],
            PermissionsRegisterEvent::class => ['onRegisterPermissions', 1000],
        ];
    }

    public function onPluginsInitialized(): void
    {
        $this->loadApiController();
    }

    public function onRegisterPermissions(PermissionsRegisterEvent $event): void
    {
        $actions = PermissionsReader::fromYaml("plugin://{$this->name}/permissions.yaml");
        $event->permissions->addActions($actions);
    }

    public function onApiRegisterRoutes(Event $event): void
    {
        $routes = $event['routes'] ?? null;
        if (!$routes || !$this->loadApiController()) {
            return;
        }

        $controller = 'Grav\\Plugin\\GravisterDerivux\\Controller\\GravisterDerivuxController';
        $routes->get('/derivux/themes', [$controller, 'themes']);
        $routes->post('/derivux/themes/validate', [$controller, 'validate']);
        $routes->post('/derivux/themes/derive', [$controller, 'create']);
    }

    public function onApiSidebarItems(Event $event): void
    {
        $items = (array) ($event['items'] ?? []);

        foreach ($items as $item) {
            if (is_array($item) && ($item['id'] ?? '') === 'gravister-derivux') {
                $event['items'] = $items;
                return;
            }
        }

        $items[] = [
            'id' => 'gravister-derivux',
            'plugin' => 'gravister-derivux',
            'label' => 'Derivux by Gravister',
            'icon' => 'fa-code-branch',
            'route' => '/plugin/gravister-derivux',
            'priority' => 10,
            'authorize' => 'api.derivux.read',
        ];

        $event['items'] = $items;
    }

    public function onApiPluginPageInfo(Event $event): void
    {
        if (($event['plugin'] ?? '') !== 'gravister-derivux') {
            return;
        }

        $event['definition'] = [
            'id' => 'gravister-derivux',
            'plugin' => 'gravister-derivux',
            'title' => 'Derivux by Gravister',
            'icon' => 'fa-code-branch',
            'page_type' => 'component',
            'component' => 'gravister-derivux',
            'authorize' => 'api.derivux.read',
        ];
    }

    private function loadApiController(): bool
    {
        if (!class_exists('Grav\\Plugin\\Api\\Controllers\\AbstractApiController')) {
            return false;
        }

        require_once __DIR__ . '/classes/Controller/GravisterDerivuxController.php';

        return class_exists(
            'Grav\\Plugin\\GravisterDerivux\\Controller\\GravisterDerivuxController',
            false
        );
    }
}
