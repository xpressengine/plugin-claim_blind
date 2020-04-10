<?php
namespace Xpressengine\Plugins\ClaimBlind;

use Xpressengine\Config\ConfigManager;

class Handler
{
    protected $configManager ;

    protected $configName = 'xe_rss';

    protected $configModuleName = 'xe_rss_modules';

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }
}
