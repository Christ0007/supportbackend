#!/bin/bash
# Script de test manuel du backend Support Platform (via curl)
# Usage : bash test_backend.sh
# Prérequis : jq installé (sudo dnf install jq) pour parser le JSON facilement.
#             Le serveur Laravel doit tourner : php artisan serve

BASE_URL="http://127.0.0.1:8000/api"

echo "======================================"
echo "  1. LOGIN ADMIN"
echo "======================================"
ADMIN_LOGIN=$(curl -s -X POST "$BASE_URL/login" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"admin@supportpro.test","password":"password"}')

echo "$ADMIN_LOGIN" | jq .
ADMIN_TOKEN=$(echo "$ADMIN_LOGIN" | jq -r '.token')

if [ "$ADMIN_TOKEN" == "null" ] || [ -z "$ADMIN_TOKEN" ]; then
  echo "❌ Échec du login admin. Vérifie l'email/mot de passe de ton seeder."
  exit 1
fi
echo "✅ Token admin récupéré."
echo ""

echo "======================================"
echo "  2. DASHBOARD ADMIN"
echo "======================================"
curl -s -X GET "$BASE_URL/dashboard" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Accept: application/json" | jq .
echo ""

echo "======================================"
echo "  3. LISTE DES UTILISATEURS (admin)"
echo "======================================"
curl -s -X GET "$BASE_URL/users" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Accept: application/json" | jq '.data[] | {id, name, email, role}'
echo ""

echo "======================================"
echo "  4. CRÉER UN TECHNICIEN (admin)"
echo "======================================"
TECH_CREATE=$(curl -s -X POST "$BASE_URL/users" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name":"Jean Testeur",
    "email":"jean.testeur@supportpro.test",
    "password":"password123",
    "role":"technician"
  }')
echo "$TECH_CREATE" | jq .
TECH_ID=$(echo "$TECH_CREATE" | jq -r '.id')
echo "✅ Technicien créé, id=$TECH_ID (attendu : company_name absent, pas d'erreur 422)"
echo ""

echo "======================================"
echo "  5. CRÉER UNE ENTREPRISE CLIENTE (admin)"
echo "======================================"
COMPANY_CREATE=$(curl -s -X POST "$BASE_URL/users" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name":"Entreprise Test",
    "email":"contact@entreprisetest.test",
    "password":"password123",
    "role":"company",
    "company_name":"Entreprise Test SARL"
  }')
echo "$COMPANY_CREATE" | jq .
echo ""

echo "======================================"
echo "  6. LISTE DES SOLUTIONS (admin)"
echo "======================================"
SOLUTIONS=$(curl -s -X GET "$BASE_URL/software-solutions" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Accept: application/json")
echo "$SOLUTIONS" | jq '.data[] | {id, name}'
SOLUTION_ID=$(echo "$SOLUTIONS" | jq -r '.data[0].id')
echo "Solution utilisée pour la suite : id=$SOLUTION_ID"
echo ""

echo "======================================"
echo "  7. ASSOCIER LE TECHNICIEN À LA SOLUTION (admin, comme référent)"
echo "======================================"
curl -s -X PUT "$BASE_URL/users/$TECH_ID" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{\"software_solution_ids\":[$SOLUTION_ID]}" | jq .
echo ""

echo "======================================"
echo "  8. LOGIN ENTREPRISE CLIENTE"
echo "======================================"
COMPANY_LOGIN=$(curl -s -X POST "$BASE_URL/login" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"contact@entreprisetest.test","password":"password123"}')
echo "$COMPANY_LOGIN" | jq .
COMPANY_TOKEN=$(echo "$COMPANY_LOGIN" | jq -r '.token')
echo ""

echo "======================================"
echo "  9. L'ENTREPRISE NE VOIT PAS ENCORE SES SOLUTIONS (pas encore associée)"
echo "======================================"
curl -s -X GET "$BASE_URL/software-solutions" \
  -H "Authorization: Bearer $COMPANY_TOKEN" \
  -H "Accept: application/json" | jq '.data'
echo "⚠️  Attendu : tableau vide, car l'entreprise n'a pas encore de solution associée."
echo ""

echo "======================================"
echo "  10. ASSOCIER LA SOLUTION À L'ENTREPRISE (admin)"
echo "======================================"
COMPANY_ID=$(echo "$COMPANY_CREATE" | jq -r '.company.id')
curl -s -X PUT "$BASE_URL/companies/$COMPANY_ID" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{\"software_solution_ids\":[$SOLUTION_ID]}" | jq .
echo ""

echo "======================================"
echo "  11. L'ENTREPRISE VOIT MAINTENANT SA SOLUTION"
echo "======================================"
curl -s -X GET "$BASE_URL/software-solutions" \
  -H "Authorization: Bearer $COMPANY_TOKEN" \
  -H "Accept: application/json" | jq '.data'
echo ""

echo "======================================"
echo "  12. L'ENTREPRISE DÉCLARE UN INCIDENT"
echo "======================================"
INCIDENT_CREATE=$(curl -s -X POST "$BASE_URL/incidents" \
  -H "Authorization: Bearer $COMPANY_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{
    \"title\":\"Erreur de connexion\",
    \"description\":\"Impossible de se connecter depuis ce matin\",
    \"priority\":\"high\",
    \"category\":\"technique\",
    \"software_solution_id\":$SOLUTION_ID
  }")
echo "$INCIDENT_CREATE" | jq .
INCIDENT_ID=$(echo "$INCIDENT_CREATE" | jq -r '.id')
echo "✅ Incident créé, id=$INCIDENT_ID, statut attendu = declared"
echo ""

echo "======================================"
echo "  13. LOGIN TECHNICIEN"
echo "======================================"
TECH_LOGIN=$(curl -s -X POST "$BASE_URL/login" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"jean.testeur@supportpro.test","password":"password123"}')
TECH_TOKEN=$(echo "$TECH_LOGIN" | jq -r '.token')
echo "✅ Token technicien récupéré."
echo ""

echo "======================================"
echo "  14. LE TECHNICIEN VOIT L'INCIDENT (référent de la solution)"
echo "======================================"
curl -s -X GET "$BASE_URL/incidents" \
  -H "Authorization: Bearer $TECH_TOKEN" \
  -H "Accept: application/json" | jq '.data[] | {id, title, status}'
echo ""

echo "======================================"
echo "  15. LE TECHNICIEN PREND EN CHARGE L'INCIDENT"
echo "======================================"
curl -s -X POST "$BASE_URL/incidents/$INCIDENT_ID/take-over" \
  -H "Authorization: Bearer $TECH_TOKEN" \
  -H "Accept: application/json" | jq .
echo ""

echo "======================================"
echo "  16. LE TECHNICIEN AJOUTE UNE INTERVENTION"
echo "======================================"
curl -s -X POST "$BASE_URL/incidents/$INCIDENT_ID/interventions" \
  -H "Authorization: Bearer $TECH_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "intervention_date":"2026-07-24",
    "duration":45,
    "description":"Analyse des logs, correction du cache de session"
  }' | jq .
echo ""

echo "======================================"
echo "  17. LE TECHNICIEN ENVOIE UN MESSAGE"
echo "======================================"
curl -s -X POST "$BASE_URL/incidents/$INCIDENT_ID/messages" \
  -H "Authorization: Bearer $TECH_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"content":"Bonjour, le problème vient du cache. Correction en cours."}' | jq .
echo ""

echo "======================================"
echo "  18. LE CLIENT REÇOIT UNE NOTIFICATION (nouveau message)"
echo "======================================"
curl -s -X GET "$BASE_URL/notifications" \
  -H "Authorization: Bearer $COMPANY_TOKEN" \
  -H "Accept: application/json" | jq .
echo ""

echo "======================================"
echo "  19. LE TECHNICIEN PASSE L'INCIDENT EN 'in_progress' PUIS 'resolved'"
echo "======================================"
curl -s -X PATCH "$BASE_URL/incidents/$INCIDENT_ID/status" \
  -H "Authorization: Bearer $TECH_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"status":"in_progress"}' | jq '{status}'

curl -s -X PATCH "$BASE_URL/incidents/$INCIDENT_ID/status" \
  -H "Authorization: Bearer $TECH_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"status":"resolved"}' | jq '{status}'
echo ""

echo "======================================"
echo "  20. LE CLIENT REFUSE LA RÉSOLUTION → retour en in_progress"
echo "======================================"
curl -s -X PATCH "$BASE_URL/incidents/$INCIDENT_ID/status" \
  -H "Authorization: Bearer $COMPANY_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"status":"in_progress"}' | jq '{status}'
echo "✅ Vérifie que le statut est bien revenu à in_progress (test de la boucle de réouverture)"
echo ""

echo "======================================"
echo "  21. LE TECHNICIEN RÉSOUT DE NOUVEAU, PUIS LE CLIENT VALIDE (closed)"
echo "======================================"
curl -s -X PATCH "$BASE_URL/incidents/$INCIDENT_ID/status" \
  -H "Authorization: Bearer $TECH_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"status":"resolved"}' | jq '{status}'

curl -s -X PATCH "$BASE_URL/incidents/$INCIDENT_ID/status" \
  -H "Authorization: Bearer $COMPANY_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"status":"closed"}' | jq '{status}'
echo ""

echo "======================================"
echo "  22. LE CLIENT ÉVALUE LA SATISFACTION (incident clôturé)"
echo "======================================"
curl -s -X POST "$BASE_URL/incidents/$INCIDENT_ID/satisfaction" \
  -H "Authorization: Bearer $COMPANY_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"rating":5,"comment":"Résolu rapidement, merci."}' | jq .
echo ""

echo "======================================"
echo "  23. TENTATIVE D'UNE 2E ÉVALUATION → doit échouer (400)"
echo "======================================"
curl -s -w "\nHTTP_STATUS:%{http_code}\n" -X POST "$BASE_URL/incidents/$INCIDENT_ID/satisfaction" \
  -H "Authorization: Bearer $COMPANY_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"rating":3,"comment":"Deuxième essai"}'
echo ""

echo "======================================"
echo "  24. HISTORIQUE DES STATUTS DE L'INCIDENT"
echo "======================================"
curl -s -X GET "$BASE_URL/incidents/$INCIDENT_ID" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Accept: application/json" | jq '.statusHistories[] | {old_status, new_status, changed_by}'
echo ""

echo "======================================"
echo "  25. TEST D'ISOLATION : le client ne doit pas voir l'incident d'une autre entreprise"
echo "======================================"
echo "(à tester manuellement avec une 2e entreprise cliente si tu veux pousser plus loin)"
echo ""

echo "======================================"
echo "  26. LOGOUT"
echo "======================================"
curl -s -X POST "$BASE_URL/logout" \
  -H "Authorization: Bearer $COMPANY_TOKEN" \
  -H "Accept: application/json" | jq .

echo ""
echo "✅ Script terminé. Relis les sorties ci-dessus pour repérer les incohérences (statuts, 403/400 inattendus, données manquantes)."