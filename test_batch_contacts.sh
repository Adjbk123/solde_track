#!/bin/bash

# Script de test pour l'enregistrement en lot de contacts
# Remplacez YOUR_TOKEN par votre token JWT réel

TOKEN="YOUR_TOKEN"
API_URL="http://localhost/api/contacts/batch"

echo "🚀 Test d'enregistrement en lot de contacts"
echo "============================================="

curl -X POST $API_URL \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d @test_batch_contacts.json \
  | jq '.'

echo ""
echo "✅ Test terminé !"
echo ""
echo "📝 Pour utiliser ce script :"
echo "1. Remplacez YOUR_TOKEN par votre token JWT"
echo "2. Ajustez l'URL si nécessaire"
echo "3. Exécutez: bash test_batch_contacts.sh"
