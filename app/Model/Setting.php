<?php

/**
 * Setting class.
 *
 * Model class for applications settings
 *
 * LICENSE: This product includes software developed at
 * the Acelle Co., Ltd. (http://acellemail.com/).
 *
 * @category   MVC Model
 *
 * @author     N. Pham <n.pham@acellemail.com>
 * @author     L. Pham <l.pham@acellemail.com>
 * @copyright  Acelle Co., Ltd
 * @license    Acelle Co., Ltd
 *
 * @version    1.0
 *
 * @link       http://acellemail.com
 */

namespace Acelle\Model;

use Illuminate\Database\Eloquent\Model;
use Acelle\Cashier\Cashier;
use Acelle\Model\Plugin;
use Acelle\Library\Facades\Billing;
use Exception;

class Setting extends Model
{
    protected $connection = 'mysql';

    public const UPLOAD_PATH = 'app/setting/';

    /**
     * Get all items.
     *
     * @return collect
     */
    public static function getAll()
    {
        $settings = self::select('*')->get();
        $result = config('default');

        foreach ($settings as $setting) {
            $result[$setting->name]['value'] = $setting->value;
        }

        return $result;
    }

    /**
     * Get setting.
     *
     * @return object
     */
    public static function get($name, $defaultValue = null)
    {
        if (config('app.sms') && in_array($name, ['frontend_scheme','backend_scheme'])) {
            return 'sms';
        }

        $setting = self::where('name', $name)->first();

        if ($setting) {
            return $setting->value;
        } elseif (isset(config('default')[$name])) {
            return config('default')[$name]['value'];
        } else {
            // @todo exception case not handled
            return $defaultValue;
        }
    }

    /**
     * Check setting EQUAL.
     *
     * @return object
     */
    public static function isYes($key)
    {
        $value = self::get($key);

        if (is_null($value)) {
            throw new Exception("No such setting: {$key}");
        }

        return strtolower($value) == 'yes';
    }

    /**
     * Set YES.
     *
     * @return object
     */
    public static function setYes($key)
    {
        return self::set($key, 'yes');
    }

    /**
     * Set setting value.
     *
     * @return object
     */
    public static function set($name, $val)
    {
        $option = self::where('name', $name)->first();

        if ($option) {
            $option->value = $val;
        } else {
            $option = new self();
            $option->name = $name;
            $option->value = $val;
        }
        $option->save();

        return $option;
    }

    /**
     * Get setting rules.
     *
     * @return object
     */
    public static function rules()
    {
        $rules = [];
        $settings = self::getAll();

        return $rules;
    }

    /**
     * Upload site logo.
     *
     * @var bool
     */
    public static function uploadSiteLogo($file, $name = null)
    {
        $path = 'images/';
        $upload_path = public_path($path);

        if (!file_exists($upload_path)) {
            mkdir($upload_path, 0777, true);
        }

        $md5file = \md5_file($file);

        $filename = $md5file.'.'.$file->getClientOriginalExtension();

        // save to server
        $file->move($upload_path, $filename);

        // create thumbnails
        $img = \Image::make($upload_path.$filename);

        self::set($name, $path.$filename);

        return true;
    }

    /**
     * Upload site logo.
     *
     * @var bool
     */
    public static function uploadFile($file, $type = null, $thumbnail = true)
    {
        $uploadPath = storage_path(self::UPLOAD_PATH);

        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        $md5file = \md5_file($file);

        $filename = $type.'-'.$md5file.'.'.$file->getClientOriginalExtension();

        // save to server
        $file->move($uploadPath, $filename);

        // create thumbnails
        if ($thumbnail) {
            $img = \Image::make($uploadPath.$filename);
        }

        self::set($type, $filename);

        return true;
    }

    /**
     * gET uploaded file location.
     *
     * @var bool
     */
    public static function getUploadFilePath($filename)
    {
        $uploadPath = storage_path(self::UPLOAD_PATH);

        return $uploadPath.$filename;
    }

    /**
     * Write default settings to DB.
     *
     * @var bool
     */
    public static function writeDefaultSettings()
    {
        foreach (config('default') as $name => $setting) {
            if (!self::where('name', $name)->exists()) {
                $value = (is_null($setting['value'])) ? '' : $setting['value'];

                $setting = new self();
                $setting->name = $name;
                $setting->value = $value;
                $setting->save();
            }
        }
    }

    public static function getTaxSettings()
    {
        if (self::get('tax') == null) {
            return [
                'enabled' => 'no',
                'default_rate' => 10,
                'countries' => [],
            ];
        }

        return json_decode(self::get('tax'), true);
    }

    public static function setTaxSettings($params)
    {
        $settings = self::getTaxSettings();
        $countries = $settings['countries'];

        if (isset($params['countries'])) {
            $countries = array_merge($countries, $params['countries']);
        }

        $settings = array_merge($settings, $params);
        $settings['countries'] = $countries;

        self::set('tax', json_encode($settings));
    }

    public static function getTaxByCountry($country = null)
    {
        if (self::getTaxSettings()['enabled'] !== 'yes') {
            return 0;
        }

        if ($country == null) {
            return self::getTaxSettings()['default_rate'];
        }

        $countries = self::getTaxSettings()['countries'];

        if (isset($countries[$country->code])) {
            return $countries[$country->code];
        } else {
            return self::getTaxSettings()['default_rate'];
        }
    }

    public static function removeTaxCountryByCode($code)
    {
        $settings = self::getTaxSettings();
        $countries = $settings['countries'];

        unset($countries[$code]);

        $settings['countries'] = $countries;

        self::set('tax', json_encode($settings));
    }

    public static function getCaptchaProvider()
    {
        $captcha = self::get('captcha_engine');

        if (in_array(
            $captcha,
            array_map(
                function ($cap) {
                    return $cap['id'];
                },
                \Acelle\Library\Facades\Hook::execute('captcha_method')
            )
        )
        ) {
            return $captcha;
        }

        return 'recaptcha';
    }

    public static function isListSignupCaptchaEnabled()
    {
        return self::get('list_sign_up_captcha') == 'yes';
    }
}
