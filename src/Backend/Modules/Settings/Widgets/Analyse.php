<?php

namespace Backend\Modules\Settings\Widgets;

use Backend\Core\Engine\Base\Widget as BackendBaseWidget;
use Backend\Modules\Settings\Engine\Model as BackendSettingsModel;

/**
 * This widget will analyze the CMS warnings
 */
class Analyse extends BackendBaseWidget
{
    public function execute(): void
    {
        $this->setColumn('left');
        $this->setPosition(1);
        $this->parse();
        $this->display();
    }

    private function parse(): void
    {
        $warnings = BackendSettingsModel::getWarnings();

        if (!empty($warnings)) {
            $this->template->assign('warnings', $warnings);
        }
    }
}
