<?php
/**
 * PHPMailer - PHP email creation and transport class.
 * Simplified version for TTS PMS
 */

namespace PHPMailer\PHPMailer;

class PHPMailer
{
    const CHARSET_ISO88591 = 'iso-8859-1';
    const CHARSET_UTF8 = 'utf-8';
    const CONTENT_TYPE_PLAINTEXT = 'text/plain';
    const CONTENT_TYPE_TEXT_CALENDAR = 'text/calendar';
    const CONTENT_TYPE_TEXT_HTML = 'text/html';
    const CONTENT_TYPE_MULTIPART_ALTERNATIVE = 'multipart/alternative';
    const CONTENT_TYPE_MULTIPART_MIXED = 'multipart/mixed';
    const CONTENT_TYPE_MULTIPART_RELATED = 'multipart/related';
    const ENCODING_7BIT = '7bit';
    const ENCODING_8BIT = '8bit';
    const ENCODING_BASE64 = 'base64';
    const ENCODING_BINARY = 'binary';
    const ENCODING_QUOTED_PRINTABLE = 'quoted-printable';
    const ENCRYPTION_STARTTLS = 'tls';
    const ENCRYPTION_SMTPS = 'ssl';
    const ICAL_METHOD_REQUEST = 'REQUEST';
    const ICAL_METHOD_PUBLISH = 'PUBLISH';
    const ICAL_METHOD_REPLY = 'REPLY';
    const ICAL_METHOD_ADD = 'ADD';
    const ICAL_METHOD_CANCEL = 'CANCEL';
    const ICAL_METHOD_REFRESH = 'REFRESH';
    const ICAL_METHOD_COUNTER = 'COUNTER';
    const ICAL_METHOD_DECLINECOUNTER = 'DECLINECOUNTER';

    public $Priority;
    public $CharSet = self::CHARSET_ISO88591;
    public $ContentType = self::CONTENT_TYPE_PLAINTEXT;
    public $Encoding = self::ENCODING_8BIT;
    public $ErrorInfo = '';
    public $From = 'root@localhost';
    public $FromName = 'Root User';
    public $Sender = '';
    public $Subject = '';
    public $Body = '';
    public $AltBody = '';
    public $Ical = '';
    public $WordWrap = 0;
    public $Mailer = 'mail';
    public $Sendmail = '/usr/sbin/sendmail';
    public $UseSendmailOptions = true;
    public $PluginDir = '';
    public $ConfirmReadingTo = '';
    public $Hostname = '';
    public $MessageID = '';
    public $MessageDate = '';
    public $Host = 'localhost';
    public $Port = 25;
    public $Helo = '';
    public $SMTPSecure = '';
    public $SMTPAutoTLS = true;
    public $SMTPAuth = false;
    public $SMTPOptions = [];
    public $Username = '';
    public $Password = '';
    public $AuthType = '';
    public $Timeout = 300;
    public $dsn = false;
    public $SMTPDebug = 0;
    public $Debugoutput = 'echo';
    public $SMTPKeepAlive = false;
    public $SingleTo = false;
    public $do_verp = false;
    public $AllowEmpty = false;
    public $DKIM_selector = '';
    public $DKIM_identity = '';
    public $DKIM_passphrase = '';
    public $DKIM_domain = '';
    public $DKIM_copyHeaderFields = true;
    public $DKIM_extraHeaders = [];
    public $DKIM_private = '';
    public $DKIM_private_string = '';
    public $action_function = '';
    public $XMailer = '';

    protected $to = [];
    protected $cc = [];
    protected $bcc = [];
    protected $ReplyTo = [];
    protected $all_recipients = [];
    protected $attachment = [];
    protected $CustomHeader = [];
    protected $lastMessageID = '';
    protected $message_type = '';
    protected $boundary = [];
    protected $language = [];
    protected $error_count = 0;
    protected $sign_cert_file = '';
    protected $sign_key_file = '';
    protected $sign_extracerts_file = '';
    protected $sign_key_pass = '';
    protected $exceptions = false;
    protected $uniqueid = '';

    const VERSION = '6.8.0';
    const STOP_MESSAGE = 0;
    const STOP_CONTINUE = 1;
    const STOP_CRITICAL = 2;
    const CRLF = "\r\n";
    const MAX_LINE_LENGTH = 998;
    const STD_LINE_LENGTH = 76;

    public function __construct($exceptions = null)
    {
        if (null !== $exceptions) {
            $this->exceptions = (bool) $exceptions;
        }
        $this->uniqueid = $this->generateId();
    }

    public function __destruct()
    {
        //Close any open SMTP connection nicely
        $this->smtpClose();
    }

    private function mailPassthru($to, $subject, $body, $header, $params)
    {
        if (ini_get('mbstring.func_overload') & 1) {
            $subject = $this->secureHeader($subject);
        } else {
            $subject = $this->encodeHeader($this->secureHeader($subject));
        }
        
        $result = false;
        if ($this->UseSendmailOptions && !empty($params)) {
            $result = mail($to, $subject, $body, $header, $params);
        } else {
            $result = mail($to, $subject, $body, $header);
        }
        
        return $result;
    }

    protected function edebug($str)
    {
        if ($this->SMTPDebug <= 0) {
            return;
        }
        
        if ($this->Debugoutput == 'error_log') {
            error_log($str);
        } else {
            echo $str;
        }
    }

    public function isSMTP()
    {
        $this->Mailer = 'smtp';
    }

    public function isMail()
    {
        $this->Mailer = 'mail';
    }

    public function isSendmail()
    {
        $ini_sendmail_path = ini_get('sendmail_path');
        if (empty($this->Sendmail) && !empty($ini_sendmail_path)) {
            $this->Sendmail = $ini_sendmail_path;
        }
        $this->Mailer = 'sendmail';
    }

    public function isQmail()
    {
        $ini_sendmail_path = ini_get('sendmail_path');
        if (empty($this->Sendmail) && !empty($ini_sendmail_path)) {
            $this->Sendmail = $ini_sendmail_path;
        }
        $this->Mailer = 'qmail';
    }

    public function addAddress($address, $name = '')
    {
        return $this->addOrEnqueueAnAddress('to', $address, $name);
    }

    public function addCC($address, $name = '')
    {
        return $this->addOrEnqueueAnAddress('cc', $address, $name);
    }

    public function addBCC($address, $name = '')
    {
        return $this->addOrEnqueueAnAddress('bcc', $address, $name);
    }

    public function addReplyTo($address, $name = '')
    {
        return $this->addOrEnqueueAnAddress('Reply-To', $address, $name);
    }

    protected function addOrEnqueueAnAddress($kind, $address, $name)
    {
        $address = trim($address);
        $name = trim(preg_replace('/[\r\n]+/', '', $name));
        if (($pos = strrpos($address, '@')) === false) {
            $error_message = sprintf('%s (%s): %s',
                $this->lang('invalid_address'),
                $kind,
                $address);
            $this->setError($error_message);
            $this->edebug($error_message);
            if ($this->exceptions) {
                throw new Exception($error_message);
            }
            return false;
        }
        $params = [$kind, $address, $name];
        if ($this->has8bitChars(substr($address, ++$pos)) && $this->idnSupported()) {
            if ('Reply-To' !== $kind) {
                if (!array_key_exists($address, $this->RecipientsQueue)) {
                    $this->RecipientsQueue[$address] = $params;
                    return true;
                }
            } else {
                if (!array_key_exists($address, $this->ReplyToQueue)) {
                    $this->ReplyToQueue[$address] = $params;
                    return true;
                }
            }
            return false;
        }
        return $this->addAnAddress($kind, $address, $name);
    }

    protected function addAnAddress($kind, $address, $name = '')
    {
        if (!in_array($kind, ['to', 'cc', 'bcc', 'Reply-To'])) {
            $error_message = sprintf('%s: %s',
                $this->lang('Invalid recipient kind'),
                $kind);
            $this->setError($error_message);
            $this->edebug($error_message);
            if ($this->exceptions) {
                throw new Exception($error_message);
            }
            return false;
        }
        if (!self::validateAddress($address)) {
            $error_message = sprintf('%s (%s): %s',
                $this->lang('invalid_address'),
                $kind,
                $address);
            $this->setError($error_message);
            $this->edebug($error_message);
            if ($this->exceptions) {
                throw new Exception($error_message);
            }
            return false;
        }
        if ('Reply-To' !== $kind) {
            if (!array_key_exists(strtolower($address), $this->all_recipients)) {
                $this->{$kind}[] = [$address, $name];
                $this->all_recipients[strtolower($address)] = true;
                return true;
            }
        } else {
            if (!array_key_exists(strtolower($address), $this->ReplyTo)) {
                $this->ReplyTo[strtolower($address)] = [$address, $name];
                return true;
            }
        }
        return false;
    }

    public function setFrom($address, $name = '', $auto = true)
    {
        $address = trim($address);
        $name = trim(preg_replace('/[\r\n]+/', '', $name));
        $pos = strrpos($address, '@');
        if (false === $pos || (empty($this->Hostname) && $auto && false === $pos)) {
            $error_message = sprintf('%s (From): %s',
                $this->lang('invalid_address'),
                $address);
            $this->setError($error_message);
            $this->edebug($error_message);
            if ($this->exceptions) {
                throw new Exception($error_message);
            }
            return false;
        }
        $this->From = $address;
        $this->FromName = $name;
        if ($auto && empty($this->Sender)) {
            $this->Sender = $address;
        }
        return true;
    }

    public function getLastMessageID()
    {
        return $this->lastMessageID;
    }

    public static function validateAddress($address, $patternselect = null)
    {
        if (null === $patternselect) {
            $patternselect = static::$validator;
        }
        if (is_callable($patternselect)) {
            return $patternselect($address);
        }
        if (strpos($address, "\n") !== false || strpos($address, "\r") !== false) {
            return false;
        }
        switch ($patternselect) {
            case 'pcre': //Fall through
            case 'pcre8':
                return (bool) filter_var($address, FILTER_VALIDATE_EMAIL);
            case 'php':
            default:
                return (bool) filter_var($address, FILTER_VALIDATE_EMAIL);
        }
    }

    public function send()
    {
        try {
            if (!$this->preSend()) {
                return false;
            }
            return $this->postSend();
        } catch (Exception $exc) {
            $this->mailHeader = '';
            $this->setError($exc->getMessage());
            if ($this->exceptions) {
                throw $exc;
            }
            return false;
        }
    }

    public function preSend()
    {
        if ('smtp' === $this->Mailer || ('mail' === $this->Mailer && (\PHP_VERSION_ID >= 80000 || stripos(PHP_OS, 'WIN') === 0))) {
            $this->mailHeader = '';
        } else {
            $this->mailHeader = $this->createHeader();
        }

        if (empty($this->Body) && empty($this->AltBody) && empty($this->Ical)) {
            $this->setError($this->lang('empty_message'));
            return false;
        }

        $this->MIMEHeader = $this->createHeader();
        $this->MIMEBody = $this->createBody();

        if ('smtp' === $this->Mailer) {
            return $this->smtpSend($this->MIMEHeader, $this->MIMEBody);
        } else {
            return true;
        }
    }

    public function postSend()
    {
        try {
            if ('sendmail' === $this->Mailer || 'qmail' === $this->Mailer) {
                return $this->sendmailSend($this->MIMEHeader, $this->MIMEBody);
            } elseif ('mail' === $this->Mailer) {
                return $this->mailSend($this->MIMEHeader, $this->MIMEBody);
            } elseif ('smtp' === $this->Mailer) {
                return true;
            } else {
                $this->setError($this->lang('mailer_not_supported') . $this->Mailer);
                return false;
            }
        } catch (Exception $exc) {
            $this->setError($exc->getMessage());
            $this->edebug($exc->toString());
            if ($this->exceptions) {
                throw $exc;
            }
        }
        return false;
    }

    protected function sendmailSend($header, $body)
    {
        $sendmailFmt = 'sendmail';
        if ($this->Mailer === 'qmail') {
            $sendmailFmt = 'qmail';
        }
        $sendmail = sprintf('%s -oi %s -f %s',
            escapeshellcmd($this->Sendmail),
            $this->UseSendmailOptions ? escapeshellarg($this->parseAddresses($this->to, $this->cc, $this->bcc, $sendmailFmt)) : '',
            escapeshellarg($this->Sender)
        );
        if ($this->SingleTo) {
            foreach ($this->SingleToArray as $toAddr) {
                $mail = @popen($sendmail, 'w');
                if (!$mail) {
                    $this->setError($this->lang('execute') . $this->Sendmail);
                    return false;
                }
                fwrite($mail, 'To: ' . $toAddr . "\n");
                fwrite($mail, $header);
                fwrite($mail, $body);
                $result = pclose($mail);
                $this->doCallback(
                    ($result === 0),
                    [$toAddr],
                    $this->cc,
                    $this->bcc,
                    $this->Subject,
                    $body,
                    $this->From,
                    []
                );
                if (0 !== $result) {
                    $this->setError($this->lang('execute') . $this->Sendmail);
                    return false;
                }
            }
        } else {
            $mail = @popen($sendmail, 'w');
            if (!$mail) {
                $this->setError($this->lang('execute') . $this->Sendmail);
                return false;
            }
            fwrite($mail, $header);
            fwrite($mail, $body);
            $result = pclose($mail);
            $this->doCallback(
                ($result === 0),
                $this->to,
                $this->cc,
                $this->bcc,
                $this->Subject,
                $body,
                $this->From,
                []
            );
            if (0 !== $result) {
                $this->setError($this->lang('execute') . $this->Sendmail);
                return false;
            }
        }
        return true;
    }

    public function isHTML($isHtml = true)
    {
        if ($isHtml) {
            $this->ContentType = static::CONTENT_TYPE_TEXT_HTML;
        } else {
            $this->ContentType = static::CONTENT_TYPE_PLAINTEXT;
        }
    }

    protected function setError($msg)
    {
        ++$this->error_count;
        if ('smtp' === $this->Mailer && null !== $this->smtp) {
            $lasterror = $this->smtp->getError();
            if (!empty($lasterror['error'])) {
                $msg .= $this->lang('smtp_error') . $lasterror['error'];
                if (!empty($lasterror['detail'])) {
                    $msg .= ' Detail: ' . $lasterror['detail'];
                }
                if (!empty($lasterror['smtp_code'])) {
                    $msg .= ' SMTP code: ' . $lasterror['smtp_code'];
                }
                if (!empty($lasterror['smtp_code_ex'])) {
                    $msg .= ' Additional SMTP info: ' . $lasterror['smtp_code_ex'];
                }
            }
        }
        $this->ErrorInfo = $msg;
    }

    public static $validator = 'php';

    protected function smtpSend($header, $body)
    {
        $header = rtrim($header, "\r\n ") . static::CRLF . static::CRLF;
        $bad_rcpt = [];
        if (!$this->smtpConnect($this->SMTPOptions)) {
            throw new Exception($this->lang('smtp_connect_failed'), self::STOP_CRITICAL);
        }
        if (!empty($this->Sender) && $this->smtp->mail($this->Sender)) {
            $callbacks = [];
            foreach (['to', 'cc', 'bcc'] as $togroup) {
                foreach ($this->{$togroup} as $to) {
                    if (!$this->smtp->recipient($to[0])) {
                        $error = $this->smtp->getError();
                        $bad_rcpt[] = ['to' => $to[0], 'error' => $error['detail']];
                        $isSent = false;
                    } else {
                        $isSent = true;
                    }
                    $callbacks[] = ['issent' => $isSent, 'to' => $to[0]];
                }
            }
            if ((count($this->all_recipients) > count($bad_rcpt)) && !$this->smtp->data($header . $body)) {
                throw new Exception($this->lang('data_not_accepted'), self::STOP_CRITICAL);
            }
            $smtp_transaction_id = $this->smtp->getLastTransactionID();
            if ($this->SMTPKeepAlive) {
                $this->smtp->reset();
            } else {
                $this->smtp->quit();
                $this->smtp->close();
            }
            foreach ($callbacks as $cb) {
                $this->doCallback(
                    $cb['issent'],
                    [[$cb['to'], '']],
                    [],
                    [],
                    $this->Subject,
                    $body,
                    $this->From,
                    ['smtp_transaction_id' => $smtp_transaction_id]
                );
            }
        } else {
            throw new Exception($this->lang('from_failed') . $this->Sender, self::STOP_CRITICAL);
        }
        if (count($bad_rcpt) > 0) {
            $errstr = '';
            foreach ($bad_rcpt as $bad) {
                $errstr .= $bad['to'] . ': ' . $bad['error'];
            }
            throw new Exception($this->lang('recipients_failed') . $errstr, self::STOP_CONTINUE);
        }
        return true;
    }

    public function smtpConnect($options = null)
    {
        if (null === $this->smtp) {
            $this->smtp = $this->getSMTPInstance();
        }
        if (null === $options) {
            $options = $this->SMTPOptions;
        }
        if ($this->smtp->connected()) {
            return true;
        }
        $this->smtp->setTimeout($this->Timeout);
        $this->smtp->setDebugLevel($this->SMTPDebug);
        $this->smtp->setDebugOutput($this->Debugoutput);
        $this->smtp->setVerp($this->do_verp);
        $hosts = explode(';', $this->Host);
        $lastexception = null;
        foreach ($hosts as $hostentry) {
            $hostinfo = [];
            if (!preg_match('/^((ssl|tls):\/\/)*([a-zA-Z0-9\.-]*|\[[a-fA-F0-9:]+\]):?([0-9]*)$/', trim($hostentry), $hostinfo)) {
                $this->edebug($this->lang('invalid_hostentry') . ' ' . trim($hostentry));
                continue;
            }
            $prefix = $hostinfo[1];
            $secure = $hostinfo[2];
            $host = $hostinfo[3];
            $port = $hostinfo[4];
            if (empty($port)) {
                $port = $this->Port;
            } else {
                $port = (int) $port;
            }
            if (empty($secure)) {
                $secure = $this->SMTPSecure;
            }
            $sslext = defined('OPENSSL_ALGO_SHA256');
            if (static::ENCRYPTION_STARTTLS === $secure || static::ENCRYPTION_SMTPS === $secure) {
                if (!$sslext) {
                    throw new Exception($this->lang('extension_missing') . 'openssl', self::STOP_CRITICAL);
                }
            }
            $host = $this->punyencodeAddress($host);
            $port = (int) $port;
            $tls = (static::ENCRYPTION_STARTTLS === $secure);
            $ssl = (static::ENCRYPTION_SMTPS === $secure);
            $timeout = $this->Timeout;
            if ($this->smtp->connect($prefix . $host, $port, $timeout, $options)) {
                try {
                    if ($this->Helo) {
                        $hello = $this->Helo;
                    } else {
                        $hello = $this->serverHostname();
                    }
                    $this->smtp->hello($hello);
                    if ($tls) {
                        if (!$this->smtp->startTLS()) {
                            throw new Exception($this->lang('connect_host'));
                        }
                        $this->smtp->hello($hello);
                    }
                    if ($this->SMTPAuth) {
                        if (!$this->smtp->authenticate(
                            $this->Username,
                            $this->Password,
                            $this->AuthType,
                            $this->oauth
                        )) {
                            throw new Exception($this->lang('authenticate'));
                        }
                    }
                    return true;
                } catch (Exception $exc) {
                    $lastexception = $exc;
                    $this->edebug($exc->getMessage());
                    $this->smtp->quit();
                }
            }
        }
        $this->smtp->close();
        if ($this->exceptions && null !== $lastexception) {
            throw $lastexception;
        }
        return false;
    }

    public function smtpClose()
    {
        if (null !== $this->smtp && $this->smtp->connected()) {
            $this->smtp->quit();
            $this->smtp->close();
        }
    }

    public function getSMTPInstance()
    {
        if (!class_exists('PHPMailer\\PHPMailer\\SMTP')) {
            require_once 'SMTP.php';
        }
        return new SMTP();
    }

    protected function lang($key)
    {
        if (count($this->language) < 1) {
            $this->setLanguage('en');
        }
        if (array_key_exists($key, $this->language)) {
            if ('smtp_connect_failed' === $key) {
                return $this->language[$key] . ' https://github.com/PHPMailer/PHPMailer/wiki/Troubleshooting';
            }
            return $this->language[$key];
        }
        return 'Language string failed to load: ' . $key;
    }

    public function setLanguage($langcode = 'en', $lang_path = '')
    {
        $renamed_langcodes = [
            'br' => 'pt_br',
            'cz' => 'cs',
            'dk' => 'da',
            'no' => 'nb',
            'se' => 'sv',
            'rs' => 'sr',
            'tg' => 'tl',
        ];
        if (array_key_exists($langcode, $renamed_langcodes)) {
            $langcode = $renamed_langcodes[$langcode];
        }
        $PHPMAILER_LANG = [
            'authenticate' => 'SMTP Error: Could not authenticate.',
            'connect_host' => 'SMTP Error: Could not connect to SMTP host.',
            'data_not_accepted' => 'SMTP Error: data not accepted.',
            'empty_message' => 'Message body empty',
            'encoding' => 'Unknown encoding: ',
            'execute' => 'Could not execute: ',
            'file_access' => 'Could not access file: ',
            'file_open' => 'File Error: Could not open file: ',
            'from_failed' => 'The following From address failed: ',
            'instantiate' => 'Could not instantiate mail function.',
            'invalid_address' => 'Invalid address: ',
            'invalid_hostentry' => 'Invalid hostentry: ',
            'invalid_host' => 'Invalid host: ',
            'mailer_not_supported' => ' mailer is not supported.',
            'provide_address' => 'You must provide at least one recipient email address.',
            'recipients_failed' => 'SMTP Error: The following recipients failed: ',
            'signing' => 'Signing Error: ',
            'smtp_connect_failed' => 'SMTP connect() failed.',
            'smtp_error' => 'SMTP server error: ',
            'variable_set' => 'Cannot set or reset variable: ',
            'extension_missing' => 'Extension missing: '
        ];
        $this->language = $PHPMAILER_LANG;
        return (bool) $this->language;
    }

    protected function generateId()
    {
        $len = 32;
        $bytes = '';
        if (function_exists('random_bytes')) {
            try {
                $bytes = random_bytes($len);
            } catch (\Exception $e) {
                //Do nothing
            }
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            /** @noinspection CryptographicallySecureRandomnessInspection */
            $bytes = openssl_random_pseudo_bytes($len);
        }
        if (empty($bytes)) {
            //We failed to produce a proper random string, so make do.
            //Use a hash to force the length to the same as the other methods
            $bytes = hash('sha256', uniqid((string) mt_rand(), true), true);
        }
        //We don't care about messing up base64 format here, just want a random string
        return str_replace(['=', '+', '/'], '', base64_encode(hash('sha256', $bytes, true)));
    }

    // Simplified methods for basic functionality
    protected function createHeader() { return ''; }
    protected function createBody() { return $this->Body; }
    protected function mailSend($header, $body) { return mail($this->addrAppend('to'), $this->Subject, $body, $header); }
    protected function addrAppend($type) { 
        $addresses = [];
        foreach ($this->{$type} as $addr) {
            $addresses[] = $addr[0];
        }
        return implode(', ', $addresses);
    }
    protected function doCallback($isSent, $to, $cc, $bcc, $subject, $body, $from, $extra) {}
    protected function parseAddresses($to, $cc, $bcc, $format) { return ''; }
    protected function serverHostname() { return 'localhost'; }
    protected function punyencodeAddress($address) { return $address; }
    protected function has8bitChars($text) { return false; }
    protected function idnSupported() { return false; }
}

class Exception extends \Exception {}
?>
