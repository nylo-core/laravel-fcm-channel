<?php

/*
|--------------------------------------------------------------------------
| SERVICE ACCOUNT JSON
|--------------------------------------------------------------------------
|
| The service account file can be downloaded from your Firebase settings.
| This can be found in Project settings > Service Accounts > Manage Service Account permissions
| Create a new key for firebase-adminsdk.
|
*/

return '{
  "type": "service_account",
  "project_id": "app-12345",
  "private_key_id": "123456789",
  "private_key": "-----BEGIN PRIVATE KEY-----\123456789\n-----END PRIVATE KEY-----\n",
  "client_email": "firebase-adminsdk-gtixj@app-12345.iam.gserviceaccount.com",
  "client_id": "123456789",
  "auth_uri": "https://accounts.google.com/o/oauth2/auth",
  "token_uri": "https://oauth2.googleapis.com/token",
  "auth_provider_x509_cert_url": "https://www.googleapis.com/oauth2/v1/certs",
  "client_x509_cert_url": "https://www.googleapis.com/robot/v1/metadata/x509/firebase-adminsdk-gtixj%40app-12345.iam.gserviceaccount.com",
  "universe_domain": "googleapis.com"
}';
