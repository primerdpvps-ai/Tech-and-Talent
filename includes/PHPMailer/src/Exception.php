<?php
/**
 * PHPMailer Exception class.
 * Simplified exception handling for TTS PMS
 */

namespace PHPMailer\PHPMailer;

class Exception extends \Exception
{
    public function errorMessage()
    {
        return '<strong>' . htmlspecialchars($this->getMessage()) . "</strong><br />\n";
    }
}
?>
