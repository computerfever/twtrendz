<?php

namespace Acelle\Library\Traits;

trait HasSettingsField
{
    public function getSetting(string $name)
    {
        $settings = $this->getAllSettings();
        return array_key_exists($name, $settings) ? $settings[$name] : null;
    }

    public function writeSetting(string $name, $value)
    {
        $settings = $this->getAllSettings();
        $settings[$name] = $value;

        $this->settings = json_encode($settings);
        $this->save();
    }

    private function getAllSettings()
    {
        if (empty($this->settings)) {
            return [];
        } else {
            return json_decode($this->settings, true);
        }
    }
}
