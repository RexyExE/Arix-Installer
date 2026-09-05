<?php

namespace Pterodactyl\Http\Controllers\Admin\Arix;

use Illuminate\View\View;
use Prologue\Alerts\AlertsMessageBag;
use Illuminate\View\Factory as ViewFactory;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Http\Requests\Admin\Arix\ArixAnnouncementRequest;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class ArixAnnouncementController extends Controller
{
    public function __construct(
        private AlertsMessageBag $alert,
        private SettingsRepositoryInterface $settings,
        private ViewFactory $view
    ) {}

    public function index(): View
    {
        return $this->view->make('admin.arix.announcement', [
            'navbar' => 'announcement',
            'sideEditor' => true,
            'announcement' => $this->settings->get('settings::arix:announcement', false),
            'announcementColor' => $this->settings->get('settings::arix:announcementColor', '#16aaaa'),
            'announcementIcon' => $this->settings->get('settings::arix:announcementIcon', 'megaphone'),
            'announcementMessage' => $this->settings->get('settings::arix:announcementMessage', 'We have a brand new game panel design!'),
            'announcementCta' => $this->settings->get('settings::arix:announcementCta', false),
            'announcementCtaTitle' => $this->settings->get('settings::arix:announcementCtaTitle', 'Buy now!'),
            'announcementCtaLink' => $this->settings->get('settings::arix:announcementCtaLink', '/'),
            'announcementDismissable' => $this->settings->get('settings::arix:announcementDismissable', false),
        ]);
    }

    public function store(ArixAnnouncementRequest $request)
    {
        foreach ($request->normalize() as $key => $value) {
            $this->settings->set('settings::' . $key, $value);
        }
        $this->alert->success('Theme settings have been updated successfully.')->flash();
        return redirect()->route('admin.arix.announcement');
    }
}