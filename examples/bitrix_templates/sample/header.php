<?php
// start .tpl handling
Beatrix::templateHeader();

// add default template section values
Beatrix::layout()->sectionsDefaults([
    '/' => [
        'sidebar.left.top' => '', // empty value means no widget
        'sidebar.left.middle' => '',
        'breadcrumbs' => '',
    ],

    '/.+' => [ // path can be any regex, this one applied on any site subdir
        'breadcrumbs' => ['layout/partials/breadcrumbs'], // use path starting from templates root (default is /.tpl)
    ],

    '/news/?.+' => [ // any pages inside /news/
        'sidebar.left.top' => ['news/menu-categories'],
    ]
]);

