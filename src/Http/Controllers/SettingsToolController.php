<?php

namespace Infinety\TemplySettings\Http\Controllers;

use App\Models\Tenant\Menu;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Infinety\TemplySettings\Http\Events\SettingsUpdatedEvent;

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
        foreach (config('temply.settings') as $tab) {
            if (str_slug($tab['name']) == $request->tab) {
                foreach ($tab['options'] as $key => $setting) {
                    if (isset($setting['rules'])) {
                        $toValidate->put($key, $setting['rules']);
                    }
                }
            }
        }

        // $fields = $request->get('fields[]');
        // $values = collect($fields)->flatMap(function ($values, $key) {
        //     return $values;
        // })->toArray();

        Validator::make($request->except(['tab']), $toValidate->toArray())->validate();

        //Save data
        foreach (config('temply.settings') as $tab) {
            if (str_slug($tab['name']) == $request->tab) {
                foreach ($tab['options'] as $key => $setting) {
                    $requestValue = $this->getRequestValue($request, $key, $setting);
                    if ($requestValue != null) {
                        setting([$key => $requestValue]);
                        setting()->save();
                    }
                }
            }
        }

        //Fire event
        event(new SettingsUpdatedEvent());

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

        $tabs = collect(config('temply.settings'))->map(function ($tab) {
            $options = collect($tab['options'])->map(function ($setting, $key) {
                $hide = (isset($setting['hide'])) ? $setting['hide'] : false;
                $options = (isset($setting['options'])) ? $setting['options'] : [];

                return $this->constructField($key, $setting['name'], $setting['type'], setting($key, null), __($setting['help']), $hide, $options);
            });

            return [
                'name'        => $tab['name'],
                'key'         => str_slug($tab['name']),
                'description' => $tab['description'],
                'options'     => $options->values()->all(),
            ];
        });

        return $tabs;
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
    private function constructField($key, $name, $type, $value, $help, $hide, $options = [])
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

        if ($key == 'main_menu' || $key == 'footer_menu') {
            $options = $this->getMenusFromDB();
        }

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

        if ($type == 'select') {
            $field['field']['options'] = $this->transformOptionsSelect($options);
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

        if ($type == 'select') {
            return 'form-select-field';
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
            Storage::disk($disk)->delete($data->path);
        }
    }

    private function getMenusFromDB()
    {
        $menus = Menu::all();

        return collect($menus)->map(function ($menu) {
            return ['label' => $menu->name, 'value' => $menu->slug];
        })->values()->all();
    }

    /**
     * @param $options
     */
    private function transformOptionsSelect($options)
    {
        return collect($options ?? [])->map(function ($label, $value) {
            return is_array($label) ? $label + ['value' => $value] : ['label' => $label, 'value' => $value];
        })->values()->all();
    }
}
