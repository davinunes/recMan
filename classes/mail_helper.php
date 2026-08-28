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
        $encodedSubject = "=?UTF-8?B?" . base64_encode($subject) . "?=";
        $headers .= "Subject: $encodedSubject\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n\r\n";
        
        $message = $headers . "--$boundary\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $message .= chunk_split(base64_encode($body)) . "\r\n";
        
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

    /**
     * Pesquisa mensagens no Gmail com base no número de protocolo (no assunto ou texto)
     */
    public static function searchMessagesByProtocolo($protocolo) {
        $protocolo = trim($protocolo);
        if (empty($protocolo)) {
            return ["found" => false, "message" => "Protocolo vazio"];
        }

        $gmail = verificarToken();
        if (!$gmail["status"] || $gmail["resta"] <= 5) {
            return ["found" => false, "error" => "Token Gmail inválido ou expirado"];
        }

        $token = $gmail["tkn"];

        // 1. Tenta buscar no Assunto (subject:"protocolo")
        $query = 'subject:"' . $protocolo . '"';
        $url = 'https://gmail.googleapis.com/gmail/v1/users/me/messages?q=' . urlencode($query) . '&maxResults=5';

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token],
            CURLOPT_TIMEOUT => 6
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $resJson = json_decode($response, true);
        $messages = $resJson['messages'] ?? [];

        // 2. Se não encontrar mensagens na busca exata por assunto, tenta busca geral pelo termo do protocolo
        if (empty($messages)) {
            $urlFallback = 'https://gmail.googleapis.com/gmail/v1/users/me/messages?q=' . urlencode($protocolo) . '&maxResults=5';
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $urlFallback,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token],
                CURLOPT_TIMEOUT => 6
            ]);
            $responseFallback = curl_exec($ch);
            curl_close($ch);
            $resJsonFallback = json_decode($responseFallback, true);
            $messages = $resJsonFallback['messages'] ?? [];
        }

        if (empty($messages)) {
            return ["found" => false, "protocolo" => $protocolo];
        }

        // 3. Obter metadados da mensagem mais recente encontrada
        $firstMsg = $messages[0];
        $msgId = $firstMsg['id'];

        $metaUrl = 'https://gmail.googleapis.com/gmail/v1/users/me/messages/' . $msgId . '?format=metadata&metadataHeaders=Subject&metadataHeaders=From&metadataHeaders=Date';
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $metaUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token],
            CURLOPT_TIMEOUT => 6
        ]);
        $metaResp = curl_exec($ch);
        curl_close($ch);

        $metaJson = json_decode($metaResp, true);
        if (!$metaJson || empty($metaJson['id'])) {
            return [
                "found" => true,
                "id" => $msgId,
                "threadId" => $firstMsg['threadId'] ?? '',
                "subject" => "E-mail com protocolo " . $protocolo,
                "from" => "",
                "date" => "",
                "snippet" => "",
                "webLink" => "https://mail.google.com/mail/#inbox/" . $msgId
            ];
        }

        $subject = "(Sem Assunto)";
        $from = "";
        $date = "";
        if (!empty($metaJson['payload']['headers'])) {
            foreach ($metaJson['payload']['headers'] as $h) {
                $hName = strtolower($h['name']);
                if ($hName === 'subject') {
                    $subject = $h['value'];
                } elseif ($hName === 'from') {
                    $from = $h['value'];
                } elseif ($hName === 'date') {
                    $date = $h['value'];
                }
            }
        }

        return [
            "found" => true,
            "id" => $msgId,
            "threadId" => $metaJson['threadId'] ?? '',
            "subject" => $subject,
            "from" => $from,
            "date" => $date,
            "snippet" => $metaJson['snippet'] ?? '',
            "webLink" => "https://mail.google.com/mail/#inbox/" . $msgId,
            "total_matches" => count($messages)
        ];
    }

    /**
     * Obtém o conteúdo completo (corpo HTML e metadados) de uma mensagem do Gmail
     */
    public static function getMessageContent($messageId) {
        $messageId = trim($messageId);
        if (empty($messageId)) {
            return ["success" => false, "error" => "ID da mensagem não informado"];
        }

        $gmail = verificarToken();
        if (!$gmail["status"] || $gmail["resta"] <= 5) {
            return ["success" => false, "error" => "Token Gmail inválido ou expirado"];
        }

        $token = $gmail["tkn"];
        $url = 'https://gmail.googleapis.com/gmail/v1/users/me/messages/' . $messageId . '?format=full';

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token],
            CURLOPT_TIMEOUT => 8
        ]);
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            return ["success" => false, "error" => "cURL Error: " . $err];
        }

        $json = json_decode($response, true);
        if (!$json || empty($json['id'])) {
            return ["success" => false, "error" => "Não foi possível carregar a mensagem no Gmail"];
        }

        $subject = "(Sem Assunto)";
        $from = "";
        $to = "";
        $date = "";
        if (!empty($json['payload']['headers'])) {
            foreach ($json['payload']['headers'] as $h) {
                $hName = strtolower($h['name']);
                if ($hName === 'subject') $subject = $h['value'];
                elseif ($hName === 'from') $from = $h['value'];
                elseif ($hName === 'to') $to = $h['value'];
                elseif ($hName === 'date') $date = $h['value'];
            }
        }

        // Extrair corpo recursivamente
        $htmlBody = '';
        $textBody = '';
        self::extractBodyParts($json['payload'], $htmlBody, $textBody);

        $finalBody = !empty($htmlBody) ? $htmlBody : (!empty($textBody) ? nl2br(htmlspecialchars($textBody)) : '<em>(E-mail sem conteúdo de texto)</em>');

        return [
            "success" => true,
            "id" => $json['id'],
            "threadId" => $json['threadId'] ?? '',
            "subject" => $subject,
            "from" => $from,
            "to" => $to,
            "date" => $date,
            "snippet" => $json['snippet'] ?? '',
            "body" => $finalBody,
            "webLink" => "https://mail.google.com/mail/#inbox/" . $json['id']
        ];
    }

    /**
     * Helper recursivo para decodificar partes de mensagem do Gmail
     */
    private static function extractBodyParts($part, &$htmlBody, &$textBody) {
        if (!empty($part['body']['data'])) {
            $data = strtr($part['body']['data'], '-_', '+/');
            $decoded = base64_decode($data);
            if (!empty($part['mimeType'])) {
                if ($part['mimeType'] === 'text/html' && empty($htmlBody)) {
                    $htmlBody = $decoded;
                } elseif ($part['mimeType'] === 'text/plain' && empty($textBody)) {
                    $textBody = $decoded;
                }
            }
        }

        if (!empty($part['parts']) && is_array($part['parts'])) {
            foreach ($part['parts'] as $subPart) {
                self::extractBodyParts($subPart, $htmlBody, $textBody);
            }
        }
    }
}

