<?php return array(
    'root' => array(
        'name' => 'repository/project',
        'pretty_version' => '1.0.0+no-version-set',
        'version' => '1.0.0.0',
        'reference' => null,
        'type' => 'project',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'dev' => true,
    ),
    'versions' => array(
        'repository/project' => array(
            'pretty_version' => '1.0.0+no-version-set',
            'version' => '1.0.0.0',
            'reference' => null,
            'type' => 'project',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'twbs/bootstrap' => array(
            'pretty_version' => 'v5.3.3',
            'version' => '5.3.3.0',
            'reference' => '6e1f75f420f68e1d52733b8e407fc7c3766c9dba',
            'type' => 'library',
            'install_path' => __DIR__ . '/../twbs/bootstrap',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'twbs/bootstrap-icons' => array(
            'pretty_version' => 'v1.11.3',
            'version' => '1.11.3.0',
            'reference' => '8d88686c03c3768a2d82ba4f20c3c4e1b100fa29',
            'type' => 'library',
            'install_path' => __DIR__ . '/../twbs/bootstrap-icons',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'twitter/bootstrap' => array(
            'dev_requirement' => false,
            'replaced' => array(
                0 => 'v5.3.3',
            ),
        ),
    ),
);
