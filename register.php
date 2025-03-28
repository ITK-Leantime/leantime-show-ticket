<?php

use Leantime\Plugins\ShowTicket\Middleware\GetLanguageAssets;
use Leantime\Core\Events\EventDispatcher;

/**
 * Adds a menu point for adding fixture data.
 *
 * @param  array<string, array<int, array<string, mixed>>> $menuStructure The existing menu structure to which the new item will be added.
 * @return array<string, array<int, array<string, mixed>>> The modified menu structure with the new item added.
 */
function addShowTicketItemToMenu(array $menuStructure): array
{
    // In the menu array, timesheets occupies spot 16 and timetable 17, lets put ticket after this...
    $menuStructure['personal'][17] = [
        'type' => 'item',
        'title' => '<span class="fas fa-fw fa-ticket"></span> Show ticket',
        'icon' => 'fa fa-fw fa-ticket',
        'tooltip' => 'View ticket',
        'href' => '/ShowTicket/ShowTicket',
        'active' => ['ShowTicket'],
        'module' => 'ShowTicket', // First part of the path when visiting your plugin e.g. leantime.dk/{this part}/ShowTicket
    ];

    return $menuStructure;
}

/**
 * Adds ShowTicket to the personal menu
 * @param array<string, array<int, array<string, mixed>>> $sections The sections in the menu is to do with which menu is displayed on the current page.
 * @return array<string, string> - the sections array, where ShowTicket.ShowTicket is in the "personal" menu.
 */
function displayPersonalMenuOnEnteringShowTicket(array $sections): array
{
    $sections['ShowTicket.showTicket'] = 'personal';

    return $sections;
}

if (class_exists(EventDispatcher::class)) {
    // https://github.com/Leantime/plugin-template/blob/main/register.php#L43-L46
    EventDispatcher::add_filter_listener(
        'leantime.core.http.httpkernel.handle.plugins_middleware',
        fn(array $middleware) => array_merge($middleware, [GetLanguageAssets::class]),
    );

    EventDispatcher::add_filter_listener('leantime.domain.menu.repositories.menu.getMenuStructure.menuStructures', 'addShowTicketItemToMenu');
    EventDispatcher::add_filter_listener('leantime.domain.menu.repositories.menu.getSectionMenuType.menuSections', 'displayPersonalMenuOnEnteringShowTicket');
}


EventDispatcher::add_event_listener(
    'leantime.core.template.tpl.*.afterScriptLibTags',
    function () {

        if (null !== (session('userdata.id')) && str_contains($_SERVER['REQUEST_URI'], '/ShowTicket/ShowTicket')) {
            $cssUrl = '/dist/css/show-ticket.css?' . http_build_query(['v' => '%%VERSION%%']);
            echo '<link rel="stylesheet" href="' . htmlspecialchars($cssUrl) . '"></link>';
            $jsUrl = '/dist/js/show-ticket.js?' . http_build_query(['v' => '%%VERSION%%']);
            echo '<script src="' . htmlspecialchars($jsUrl) . '"></script>';
        }
    },
    5
);
