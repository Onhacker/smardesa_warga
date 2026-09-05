<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Verification_workflow
{
    public static function settings(array $settings)
    {
        return array(
            'sekdes' => array_key_exists('sekdes', $settings) ? (bool) $settings['sekdes'] : true,
            'kades' => array_key_exists('kades', $settings) ? (bool) $settings['kades'] : true
        );
    }

    public static function actions($role, $status, array $settings)
    {
        $flow = self::settings($settings);
        if ($role === 'admin-desa') {
            if ($status === 'submitted') return array('verify', 'revision', 'reject');
            if ($status === 'verified') return array('approve', 'revision', 'reject');
        }
        // With no mobile verifier selected, the local office handles approval.
        if (!$flow['sekdes'] && !$flow['kades']) return array();
        if ($status === 'submitted') {
            if ($flow['sekdes'] && $role === 'sekdes') {
                return array($flow['kades'] ? 'verify' : 'approve', 'revision', 'reject');
            }
            if (!$flow['sekdes'] && $flow['kades'] && $role === 'kepala-desa') {
                return array('approve', 'revision', 'reject');
            }
        }
        if ($status === 'verified' && $flow['kades'] && $role === 'kepala-desa') {
            return array('approve', 'revision', 'reject');
        }
        return array();
    }
}
