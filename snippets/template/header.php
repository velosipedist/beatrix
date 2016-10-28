<?
// capture anything printed on site before template rendering
beatrix\template_header();

// if you want to automate sections depending on addresses
beatrix\widgets()->sectionsDefaults([
    // empty default sections
    '/' => [
        'sidebar.left.top' => '',
        'sidebar.left.middle' => '',
        'sidebar.left.bottom' => '',
        'sidebar.right.top' => '',
        'sidebar.right.middle' => '',
        'sidebar.right.bottom' => '',
    ],
    // any sub-page has left menu
    '/.+' => [
        'sidebar.left.top' => ['layout/snippets/sidebar-menu-left'],
    ],
    // but not in search page
    '/search/?.+' => [
        'sidebar.left.top' => '',
    ]
]);
