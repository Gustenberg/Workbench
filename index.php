<?php

require_once __DIR__ . '/../models/Setting.php';
require_once __DIR__ . '/../models/AuditLog.php';

class SettingsController {
    private $settingModel;
    private $auditLogModel;
    private $auth;

    public function __construct($auth) {
        $this->settingModel = new Setting();
        $this->auditLogModel = new AuditLog();
        $this->auth = $auth;
    }

    public function index() {
        $this->auth->requireRole('admin');
        $settings = $this->settingModel->getAllSettings();
        echo json_encode($settings);
    }

    public function update() {
        $this->auth->requireRole('admin');
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data) || !is_array($data)) {
            http_response_code(400);
            echo json_encode(['message' => 'Invalid data provided.']);
            return;
        }

        $success = true;
        foreach ($data as $key => $value) {
            $oldValue = $this->settingModel->getSettingByKey($key);
            if (!$this->settingModel->updateSetting($key, $value)) {
                $success = false;
                break;
            }
            $this->auditLogModel->addLog($_SESSION['user_id'], 'update_setting', 'setting', null, ['key' => $key, 'value' => $oldValue], ['key' => $key, 'value' => $value]);
        }

        if ($success) {
            echo json_encode(['message' => 'Settings updated successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Failed to update some settings']);
        }
    }

    public function getGoogleMapsApiKey() {
        // No role requirement as this key is client-side and restricted by domain
        echo json_encode(['key' => GOOGLE_MAPS_JAVASCRIPT_API_KEY]);
    }
}