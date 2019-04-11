<?php

namespace Infinety\TemplySettings\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SettingsToolController extends Controller
{
    public function getSettings()
    {
        return response()->json([
            'fields' => $this->getSettingsFromDB(),
        ]);
    }

    /**
     * @param Request $request
     */
    public function saveSettings(Request $request)
    {
        //Validation
        $toValidate = collect([]);
        foreach (config('temply.settings') as $key => $setting) {
            if (isset($setting['rules'])) {
                $toValidate->put($key, $setting['rules']);
            }
        }

        Validator::make($request->all(), $toValidate->toArray())->validate();

        //Save data
        foreach (config('temply.settings') as $key => $setting) {
            $requestValue = $this->getRequestValue($request, $key, $setting);

            if ($setting['type'] == 'image') {
                if ($requestValue != null) {
                    setting([$key => $requestValue]);
                    setting()->save();
                }
            } else {
                if ($requestValue === null) {
                    $requestValue = '';
                }
                setting([$key => $requestValue]);
                setting()->save();
            }
        }

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * @return mixed
     */
    private function getSettingsFromDB()
    {
        $settings = [];

        foreach (config('temply.settings') as $key => $setting) {
            $hide = (isset($setting['hide'])) ? $setting['hide'] : false;

            $settings[] = $this->constructField($key, $setting['name'], $setting['type'], setting($key, null), __($setting['help']), $hide);
        }

        return $settings;
    }

    /**
     * @param $request
     * @param $key
     * @param $setting
     * @return mixed
     */
    private function getRequestValue($request, $key, $setting)
    {
        if ($setting['type'] == 'image') {
            if ($request->{$key} == null) {
                return null;
            }
            //Delete previus
            $this->deletePreviusImage($key, $setting);

            $disk = (isset($setting['disk'])) ? $setting['disk'] : 'public';

            $path = $request->{$key}->store('', $disk);

            return json_encode([
                'path' => $path,
                'url'  => Storage::disk($disk)->url($path),
                'size' => $request->{$key}->getSize(),
                'name' => $request->{$key}->getClientOriginalName(),
            ]);
        }

        return $request->get($key);
    }

    /**
     * @param $name
     * @param $type
     * @param $value
     * @param $help
     */
    private function constructField($key, $name, $type, $value, $help, $hide)
    {
        $field = [
            'vue'    => $this->getNovaType($type),
            'name'   => __($name),
            'errors' => [],
            'hide'   => $hide,
            'field'  => [
                'name'      => __($name),
                'attribute' => $key,
                'component' => $this->getNovaComponent($type),
                'value'     => $this->getNovaValue($type, $value),
                'helpText'  => __($help),
            ],
        ];

        if ($type == 'image') {
            $field['field']['previewUrl'] = $this->getFileUrl($value);
            $field['field']['thumbnailUrl'] = $this->getFileUrl($value);

            if (setting($key, false)) {
                $field['field']['file'] = json_decode($value);
            }
        }

        if ($type == 'place') {
            $field['field']['latitude'] = 'latitude';
            $field['field']['longitude'] = 'longitude';
        }

        return $field;
    }

    /**
     * @param $type
     */
    private function getNovaType($type)
    {
        if ($type == 'image') {
            return 'form-file-field';
        }

        if ($type == 'place') {
            return 'form-place-field';
        }

        if ($type == 'textarea') {
            return 'form-textarea-field';
        }

        return 'form-text-field';
    }

    /**
     * @param $type
     * @param $value
     */
    private function getNovaValue($type, $value)
    {
        if ($value == null) {
            return null;
        }
        if ($type == 'image') {
            $data = json_decode($value);

            return $data->path;
        }

        return $value;
    }

    /**
     * @param $type
     */
    private function getNovaComponent($type)
    {
        if ($type == 'image') {
            return 'file-field';
        }

        if ($type == 'place') {
            return 'place-field';
        }

        if ($type == 'textarea') {
            return 'textarea-field';
        }

        return 'text-field';
    }

    /**
     * @param $value
     * @return mixed
     */
    private function getFileUrl($value)
    {
        if ($value == null) {
            return null;
        }
        $data = json_decode($value);

        return $data->url;
    }

    /**
     * @param $key
     */
    private function deletePreviusImage($key, $setting)
    {
        $data = setting($key, false);
        if ($data !== false) {
            $disk = (isset($setting['disk'])) ? $setting['disk'] : 'public';
            $data = json_decode($data);
            if (optional($data)->path != null) {
                Storage::disk($disk)->delete($data->path);
            }
        }
    }
}
