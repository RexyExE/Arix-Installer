<?php

namespace Pterodactyl\Http\Controllers\Admin\Arix;

use Illuminate\View\View;
use Prologue\Alerts\AlertsMessageBag;
use Illuminate\View\Factory as ViewFactory;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Http\Requests\Admin\Arix\ArixColorsRequest;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class ArixColorsController extends Controller
{
    public function __construct(
        private AlertsMessageBag $alert,
        private SettingsRepositoryInterface $settings,
        private ViewFactory $view
    ) {}

    public function index(): View
    {
        return $this->view->make('admin.arix.colors', [
            'navbar' => 'colors',
            'sideEditor' => true,
            'primary' => $this->settings->get('settings::arix:primary', '#4A35CF'),
            'successText' => $this->settings->get('settings::arix:successText', '#E1FFD8'),
            'successBorder' => $this->settings->get('settings::arix:successBorder', '#56AA2B'),
            'successBackground' => $this->settings->get('settings::arix:successBackground', '#3D8F1F'),
            'dangerText' => $this->settings->get('settings::arix:dangerText', '#FFD8D8'),
            'dangerBorder' => $this->settings->get('settings::arix:dangerBorder', '#AA2A2A'),
            'dangerBackground' => $this->settings->get('settings::arix:dangerBackground', '#8F1F20'),
            'secondaryText' => $this->settings->get('settings::arix:secondaryText', '#B2B2C1'),
            'secondaryBorder' => $this->settings->get('settings::arix:secondaryBorder', '#42425B'),
            'secondaryBackground' => $this->settings->get('settings::arix:secondaryBackground', '#2B2B40'),
            'gray50' => $this->settings->get('settings::arix:gray50', '#F4F4F4'),
            'gray100' => $this->settings->get('settings::arix:gray100', '#D5D5DB'),
            'gray200' => $this->settings->get('settings::arix:gray200', '#B2B2C1'),
            'gray300' => $this->settings->get('settings::arix:gray300', '#8282A4'),
            'gray400' => $this->settings->get('settings::arix:gray400', '#5E5E7F'),
            'gray500' => $this->settings->get('settings::arix:gray500', '#42425B'),
            'gray600' => $this->settings->get('settings::arix:gray600', '#2B2B40'),
            'gray700' => $this->settings->get('settings::arix:gray700', '#1D1D37'),
            'gray800' => $this->settings->get('settings::arix:gray800', '#0B0D2A'),
            'gray900' => $this->settings->get('settings::arix:gray900', '#040519'),
            'lightmode_primary' => $this->settings->get('settings::arix:lightmode_primary', '#4A35CF'),
            'lightmode_successText' => $this->settings->get('settings::arix:lightmode_successText', '#E1FFD8'),
            'lightmode_successBorder' => $this->settings->get('settings::arix:lightmode_successBorder', '#56AA2B'),
            'lightmode_successBackground' => $this->settings->get('settings::arix:lightmode_successBackground', '#3D8F1F'),
            'lightmode_dangerText' => $this->settings->get('settings::arix:lightmode_dangerText', '#FFD8D8'),
            'lightmode_dangerBorder' => $this->settings->get('settings::arix:lightmode_dangerBorder', '#AA2A2A'),
            'lightmode_dangerBackground' => $this->settings->get('settings::arix:lightmode_dangerBackground', '#8F1F20'),
            'lightmode_secondaryText' => $this->settings->get('settings::arix:lightmode_secondaryText', '#46464D'),
            'lightmode_secondaryBorder' => $this->settings->get('settings::arix:lightmode_secondaryBorder', '#C0C0D3'),
            'lightmode_secondaryBackground' => $this->settings->get('settings::arix:lightmode_secondaryBackground', '#A6A7BD'),
            'lightmode_gray50' => $this->settings->get('settings::arix:lightmode_gray50', '#141415'),
            'lightmode_gray100' => $this->settings->get('settings::arix:lightmode_gray100', '#27272C'),
            'lightmode_gray200' => $this->settings->get('settings::arix:lightmode_gray200', '#46464D'),
            'lightmode_gray300' => $this->settings->get('settings::arix:lightmode_gray300', '#626272'),
            'lightmode_gray400' => $this->settings->get('settings::arix:lightmode_gray400', '#757689'),
            'lightmode_gray500' => $this->settings->get('settings::arix:lightmode_gray500', '#A6A7BD'),
            'lightmode_gray600' => $this->settings->get('settings::arix:lightmode_gray600', '#C0C0D3'),
            'lightmode_gray700' => $this->settings->get('settings::arix:lightmode_gray700', '#E7E7EF'),
            'lightmode_gray800' => $this->settings->get('settings::arix:lightmode_gray800', '#F0F1F5'),
            'lightmode_gray900' => $this->settings->get('settings::arix:lightmode_gray900', '#FFFFFF'),
        ]);
    }

    public function store(ArixColorsRequest $request)
    {
        foreach ($request->normalize() as $key => $value) {
            $this->settings->set('settings::' . $key, $value);
        }
        $this->alert->success('Theme settings have been updated successfully.')->flash();
        return redirect()->route('admin.arix.colors');
    }
}