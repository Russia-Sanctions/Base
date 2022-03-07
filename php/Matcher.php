<?php

namespace RussiaSanctions;

/**
 * Matches given IPv4 addresses against the binary database
 */
class Matcher
{
    /**
     * File handle to the binary database
     */
    private $fp;

    /**
     * Opens the binary database for reading
     */
    public function __construct()
    {
        $this->fp = fopen(__DIR__ . "../trees/ipv4.bin", "r");
    }

    /**
     * Closes the binary database cleanly
     */
    public function __destruct()
    {
        fclose($this->fp);
    }

    /**
     * Searches the binary database for the given IPv4 address and
     * returns its ISO 2 letter country code if it is found
     *
     * @param string $ipString
     * @return ?string Country Code or null if not matched
     */
    public function matchIP($ipStr)
    {
        $ip = ip2long($ipStr);
        $isRight = false;
        fseek($this->fp, 0);

        while (1) {
            $data = unpack("Nprefix/Nmask/Nlen", fread($this->fp, 12));

            if (($data['len'] & 0xffff0000) === 0xffff0000) {
                $length = 0;
                $type = chr($data['len'] >> 8) . chr($data['len']);
            } else {
                $length = $data['len'];
                $type = null;
            }

            if (($ip & $data['mask']) === $data['prefix']) {
                if ($type) {
                    return $type;
                }
                $isRight = false;
            } else {
                // No match
                if ($isRight) {
                    return null;
                } else {
                    $isRight = true;
                    fseek($this->fp, $length, SEEK_CUR);
                }
            }
        }
        return null;
    }
}

