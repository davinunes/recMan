<?php

class MailHelper {
    public static function buildMimeMessage($to, $subject, $body, $cc = [], $bcc = [], $attachments = []) {
        $boundary = uniqid('np');
        
        $headers = "To: $to\r\n";
        if (!empty($cc)) {
            $headers .= "Cc: " . implode(', ', $cc) . "\r\n";
        }
        if (!empty($bcc)) {
            $headers .= "Bcc: " . implode(', ', $bcc) . "\r\n";
        }
        $headers .= "Subject: $subject\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n\r\n";
        
        $message = "--$boundary\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $message .= "$body\r\n\r\n";
        
        foreach ($attachments as $attachment) {
            if (file_exists($attachment['path'])) {
                $filename = $attachment['name'];
                $content = file_get_contents($attachment['path']);
                $content = chunk_split(base64_encode($content));
                
                $message .= "--$boundary\r\n";
                $message .= "Content-Type: application/octet-stream; name=\"$filename\"\r\n";
                $message .= "Content-Description: $filename\r\n";
                $message .= "Content-Disposition: attachment; filename=\"$filename\"; size=" . filesize($attachment['path']) . ";\r\n";
                $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
                $message .= "$content\r\n\r\n";
            }
        }
        
        $message .= "--$boundary--";
        
        return $message;
    }

    public static function sendViaGmail($mime) {
        $gmail = verificarToken();
        if (!$gmail["status"] || $gmail["resta"] <= 5) {
            return ["error" => "Token Gmail inválido ou expirado"];
        }

        $token = $gmail["tkn"];
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://www.googleapis.com/upload/gmail/v1/users/me/messages/send?uploadType=media',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $mime,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: message/rfc822',
                'Authorization: Bearer ' . $token
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            return ["error" => "cURL Error: " . $err];
        }

        return json_decode($response, true);
    }
}
