<?php
class CountryIPChecker {
    private $ipv4;
    private $ipv6;

    public function __construct($ipv4File, $ipv6File) {
        if (file_exists($ipv4File)) include($ipv4File);
        if (file_exists($ipv6File)) include($ipv6File);
        $this->ipv4 = isset($ipv4) ? $ipv4 : [];
        $this->ipv6 = isset($ipv6) ? $ipv6 : [];
    }

    public function getClientIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) return $_SERVER['HTTP_CLIENT_IP'];
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        return $_SERVER['REMOTE_ADDR'] ?? null;
    }

    public function getIPVersion($ip) {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) return 'IPv4';
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) return 'IPv6';
        return null;
    }

    public function is_country_ip($ip = null) {
        $ip = $ip ?? $this->getClientIP();
        $version = $this->getIPVersion($ip);
        if ($version === 'IPv4') return $this->is_IPv4($ip);
        if ($version === 'IPv6') return $this->is_IPv6($ip);
        return false;
    }

    private function is_IPv4($ip) {
        foreach ($this->ipv4 as $range) {
            if ($this->ipInRange($ip, $range)) return true;
        }
        return false;
    }

    private function is_IPv6($ip) {
        foreach ($this->ipv6 as $range) {
            if ($this->ipInRange($ip, $range)) return true;
        }
        return false;
    }

    private function ipInRange($ip, $range) {
        if (strpos($range, '/') === false) {
            $range .= strpos($ip, ':') !== false ? '/128' : '/32';
        }

        list($rangeIP, $netmask) = explode('/', $range, 2);
        $ipBin = inet_pton($ip);
        $rangeBin = inet_pton($rangeIP);
        if ($ipBin === false || $rangeBin === false) return false;

        $maskBytes = floor($netmask / 8);
        $maskBits = $netmask % 8;

        for ($i = 0; $i < $maskBytes; $i++) {
            if ($ipBin[$i] !== $rangeBin[$i]) return false;
        }

        if ($maskBits > 0) {
            $mask = ~(255 >> $maskBits) & 255;
            if ((ord($ipBin[$maskBytes]) & $mask) !== (ord($rangeBin[$maskBytes]) & $mask)) return false;
        }

        return true;
    }
}
