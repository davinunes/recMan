<?php

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
  CURLOPT_POSTFIELDS =>'Content-Type: multipart/mixed; boundary=foo_bar_baz
MIME-Version: 1.0
to: davi.nunes@gmail.com
subject: POSTMAN Rest API Execution

--foo_bar_baz
Esse e-mail tem um anexo

--foo_bar_baz
Content-Type: application/png
MIME-Version: 1.0
Content-Transfer-Encoding: base64
Content-Disposition: attachment; filename="anexo.png"

[base64content]

--foo_bar_baz--',
  CURLOPT_HTTPHEADER => array(
    'Content-Type: message/rfc822',
    'Authorization: Bearer ya29.a0AfB_byAK1pKxvclY1gyR63UgUF7efjZRItXMuJ5a1p10Rql_HO-scswe4WrK-Az5Ab6JJDT5yYdsNLD1JSnzMrvWURKuAnvl_9UjeabEFh62-7yha1mscvTmMFydMS0jpH5PA2fFG2K4UjTMY-mYHtg1XHCw_v2x7JMaCgYKAdQSARASFQHGX2Mij08BpjH37MJlQ1NT4sCTVg0170'
  ),
));

$response = curl_exec($curl);

curl_close($curl);
echo $response;


?>