<?php

declare(strict_types=1);

class Auth
{
    public static function roleId(): int
    {
        return (int)($_SESSION['role_id'] ?? 0);
    }

    public static function isAuditee(): bool
    {
        return self::roleId() === 4;
    }

    /**
     * Pimpinan: role_id 5, khusus melihat (dashboard, laporan, capaian)
     * di seluruh Unit/Institusi. Tidak bisa mengelola/mengedit apa pun.
     */
    public static function isPimpinan(): bool
    {
        return self::roleId() === 5;
    }

    /**
     * Bisa Tambah/Edit/Hapus data (Admin, Ketua LPM, Auditor).
     * Auditee & Pimpinan cuma bisa lihat & print.
     */
    public static function canManage(): bool
    {
        return !self::isAuditee() && !self::isPimpinan();
    }

 /**
     * Modul RTM/RTL: Admin, Ka. LPM, dan Auditee boleh mengelola penuh.
     * Auditor tidak diberi akses mengelola (cuma memantau).
     */
    public static function canManageRtm(): bool
    {
        $role = self::roleId();

        return $role === 1 || $role === 2 || $role === 4;
    }
    /**
     * Khusus validasi Pelaksanaan RTL: Admin & Ka. LPM.
     */
    public static function canVerifyRtl(): bool
    {
        $role = self::roleId();

        return $role === 1 || $role === 2;
    }
    /**
     * PTP: Admin, Ka. LPM, dan Auditee sama-sama boleh mengelola.
     * Auditor tidak diberi akses.
     */
    public static function canManagePtp(): bool
    {
        $role = self::roleId();

        return $role === 1 || $role === 2 || $role === 4;
    }
    /**
     * Manajemen User: khusus Administrator.
     */
    public static function isAdmin(): bool
    {
        return self::roleId() === 1;
    }

        public static function isDosen(): bool
    {
        return self::roleId() === 6;
    }

    public static function isMahasiswa(): bool
    {
        return self::roleId() === 7;
    }

    public static function getDosenId(): int
    {
        return (int) ($_SESSION['dosen_id'] ?? 0);
    }

    public static function getMahasiswaId(): int
    {
        return (int) ($_SESSION['mahasiswa_id'] ?? 0);
    }
}