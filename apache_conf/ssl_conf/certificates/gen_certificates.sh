#!/bin/sh

# This script accepts a domain name as input
if [ "$#" -ne 1 ]
then
    echo "Usage: Provide a domain name"
    exit 1
fi

DOMAIN=$1

# CA CERTIFICATE SETUP

# Generate the CA's private key
openssl genrsa -des3 -out SNH_CA.key 2048
# Create the CA's self-signed certificate
openssl req -x509 -new -nodes -key SNH_CA.key -sha256 -days 1825 -out SNH_CA.pem
# Install the CA certificate into the system's trusted certificate store
sudo cp SNH_CA.pem /usr/local/share/ca-certificates/SNH_CA.crt
# Verify the CA certificate details
openssl x509 -in /usr/local/share/ca-certificates/SNH_CA.crt -noout -text
sudo update-ca-certificates
# Check if the CA certificate is correctly installed
awk -v cmd='openssl x509 -noout -subject' '/BEGIN/{close(cmd)};{print | cmd}' < /etc/ssl/certs/ca-certificates.crt | grep UNIPI

# SERVER CERTIFICATE SETUP

# Generate the server's private key
openssl genrsa -out $DOMAIN.key 2048
# Create a certificate signing request (CSR) for the server
openssl req -new -key $DOMAIN.key -out $DOMAIN.csr
# Generate an X509 V3 extension file for the server certificate
cat > $DOMAIN.ext << EOF
authorityKeyIdentifier=keyid,issuer
basicConstraints=CA:FALSE
keyUsage = digitalSignature, nonRepudiation, keyEncipherment, dataEncipherment
subjectAltName = @alt_names
[alt_names]
DNS.1 = *.$DOMAIN
EOF
# Sign the server certificate with the CA certificate and key
openssl x509 -req -in $DOMAIN.csr -CA SNH_CA.pem -CAkey SNH_CA.key -CAcreateserial \
-out $DOMAIN.crt -days 825 -sha256 -extfile $DOMAIN.ext

# Organize the CA files and server files into directories
mkdir ca
mv SNH_CA.* ca
mkdir bookselling
mv bookselling.* bookselling