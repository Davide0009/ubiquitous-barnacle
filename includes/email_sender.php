<?php
// Note: This script uses PHP's native sockets (stream_socket_client) to connect
// directly to the SMTP server and send the email, avoiding the need for large
// external libraries like PHPMailer or SwiftMailer.

/**
 * Sends the OTP email using a direct, secure SMTP socket connection.
 *
 * @param string $recipientEmail The email address to send the OTP to.
 * @param string $otp The 6-digit verification code.
 * @return bool True on successful send, false otherwise.
 */
function send_otp_email(string $recipientEmail, string $otp): bool {
    
    // ----------------------------------------------------------------------
    // !!! CRITICAL: UPDATE THESE SMTP CONFIGURATION DETAILS !!!
    // You MUST use a Gmail App Password for the $smtpPassword.
    // ----------------------------------------------------------------------
    $smtpHost = 'ssl://smtp.gmail.com'; // Use 'ssl://' prefix for port 465 (SMTPS)
    $smtpPort = 465;                  // 465 is the standard secure SMTPS port
    
    // **CRITICAL NOTE:** Replace the placeholder with the actual Google App Password.
    $smtpUsername = 'mofindavid26@gmail.com'; 
    $smtpPassword = 'yqmnsytfcvcxazzb'; 
    
    $senderEmail = 'mofindavid26@gmail.com';
    $senderName = 'School Portal Verification';
    // ----------------------------------------------------------------------

    // --- 1. Compose the Email Content (Headers & Body) ---
    $subject = 'Your School Portal Verification Code';
    $body = "Hello,\n\nYour new 6-digit verification code for the School Portal is: {$otp}.\n\nPlease use this code on the verification page to complete your registration.\n\nThank you,\nThe School Portal Team";
    
    $headers = "From: " . $senderName . " <" . $senderEmail . ">\r\n";
    $headers .= "To: " . $recipientEmail . "\r\n";
    $headers .= "Subject: " . $subject . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=utf-8\r\n";
    $headers .= "Content-Transfer-Encoding: 8bit\r\n";
    $headers .= "\r\n" . $body;

    $socket = null;
    $log_error = function($message) use ($recipientEmail) {
        error_log("OTP Email failed (Native Socket) to {$recipientEmail}. Error: {$message}");
    };

    try {
        // --- 2. Open the Secure Socket Connection ---
        $socket = stream_socket_client(
            $smtpHost . ":" . $smtpPort, 
            $errno, 
            $errstr, 
            30, 
            STREAM_CLIENT_CONNECT
        );

        if (!$socket) {
            $log_error("Socket connection failed: [{$errno}] {$errstr}");
            return false;
        }

        // Helper function to read response from server
        $read_response = function() use ($socket) {
            $response = '';
            while ($str = fgets($socket, 512)) {
                $response .= $str;
                // Check if this is the last line of the response (RFC 821)
                if (substr($str, 3, 1) == ' ') break; 
            }
            return $response;
        };

        // Helper function to send command and check response code
        $send_command = function($command, $expected_code, $error_msg) use ($socket, $read_response, $log_error) {
            fputs($socket, $command . "\r\n");
            $response = $read_response();
            if (intval(substr($response, 0, 3)) != $expected_code) {
                $log_error("SMTP Command failed. {$error_msg}: " . trim($response));
                return false;
            }
            return true;
        };
        
        // Read initial greeting (expected 220)
        $read_response(); 

        // --- 3. SMTP Conversation ---
        
        // EHLO - Extended Hello
        if (!$send_command("EHLO localhost", 250, "EHLO failed")) return false;

        // AUTH LOGIN (for basic authentication)
        if (!$send_command("AUTH LOGIN", 334, "AUTH LOGIN failed")) return false;

        // Send Username (Base64 encoded)
        $encoded_username = base64_encode($smtpUsername);
        if (!$send_command($encoded_username, 334, "Username send failed")) return false;

        // Send Password (Base64 encoded)
        $encoded_password = base64_encode($smtpPassword);
        if (!$send_command($encoded_password, 235, "Password (App Password) failed to authenticate. CRITICAL!")) return false;
        
        // MAIL FROM
        if (!$send_command("MAIL FROM:<" . $senderEmail . ">", 250, "MAIL FROM failed")) return false;

        // RCPT TO
        if (!$send_command("RCPT TO:<" . $recipientEmail . ">", 250, "RCPT TO failed")) return false;

        // DATA command
        if (!$send_command("DATA", 354, "DATA command failed")) return false;

        // Send headers and body, followed by a dot on its own line to signal end of data
        fputs($socket, $headers . "\r\n.\r\n");
        $response = $read_response();
        if (intval(substr($response, 0, 3)) != 250) {
            $log_error("Failed to send message body: " . trim($response));
            return false;
        }

        // QUIT
        $send_command("QUIT", 221, "QUIT failed (Cleanup)");
        
        fclose($socket);
        return true;

    } catch (\Exception $e) {
        $log_error("An unexpected error occurred: " . $e->getMessage());
        
        if ($socket && is_resource($socket)) {
            fclose($socket);
        }
        return false;
    }
}
?>