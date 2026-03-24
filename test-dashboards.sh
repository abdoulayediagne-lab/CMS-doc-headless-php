#!/bin/bash

# Script de test pour les dashboards
# Utilisation: ./test-dashboards.sh [--admin|--editor|--author|--reader|--all]

API_BASE="http://localhost:8080"
CREDENTIALS=(
  "admin:admin@cms.fr:password123"
  "editeur:editeur@cms.fr:password123"
  "auteur:auteur@cms.fr:password123"
  "lecteur:lecteur@cms.fr:password123"
)

# Couleurs pour le terminal
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Fonction pour afficher les titres
print_header() {
  echo -e "\n${BLUE}════════════════════════════════════════${NC}"
  echo -e "${BLUE}$1${NC}"
  echo -e "${BLUE}════════════════════════════════════════${NC}\n"
}

# Fonction pour afficher les résultats
print_result() {
  echo -e "${GREEN}✅ $1${NC}"
}

# Fonction pour afficher les erreurs
print_error() {
  echo -e "${RED}❌ $1${NC}"
}

# Fonction pour afficher les infos
print_info() {
  echo -e "${YELLOW}ℹ️  $1${NC}"
}

# Fonction pour tester un dashboard
test_dashboard() {
  local role=$1
  local email=$2
  local password=$3

  print_header "Testing ${role^^} Dashboard"

  # 1. Login
  print_info "Logging in as $email..."
  LOGIN_RESPONSE=$(curl -s -X POST "$API_BASE/auth/login" \
    -H "Content-Type: application/json" \
    -d "{\"email\":\"$email\",\"password\":\"$password\"}")

  TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r '.access_token' 2>/dev/null)

  if [ -z "$TOKEN" ] || [ "$TOKEN" = "null" ]; then
    print_error "Failed to get token for $role"
    echo "Response: $LOGIN_RESPONSE"
    return 1
  fi

  print_result "Login successful"
  print_info "Token: ${TOKEN:0:30}..."

  # 2. Call dashboard
  print_info "Fetching dashboard..."
  DASHBOARD_RESPONSE=$(curl -s -X GET "$API_BASE/dashboard" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Content-Type: application/json")

  # Check if response contains error
  ERROR=$(echo "$DASHBOARD_RESPONSE" | jq -r '.error' 2>/dev/null)
  if [ ! -z "$ERROR" ] && [ "$ERROR" != "null" ]; then
    print_error "Dashboard error: $ERROR"
    return 1
  fi

  print_result "Dashboard retrieved successfully"

  # 3. Display dashboard data
  echo -e "${BLUE}Dashboard Data:${NC}"
  echo "$DASHBOARD_RESPONSE" | jq '.' 2>/dev/null || echo "$DASHBOARD_RESPONSE"

  return 0
}

# Fonction pour tester l'accès non authentifié
test_unauthorized() {
  print_header "Testing Unauthorized Access"

  print_info "Calling dashboard without token..."
  RESPONSE=$(curl -s -X GET "$API_BASE/dashboard" \
    -H "Content-Type: application/json")

  ERROR=$(echo "$RESPONSE" | jq -r '.error' 2>/dev/null)
  if [ "$ERROR" = "missing bearer token" ]; then
    print_result "Correctly rejected: $ERROR"
  else
    print_error "Expected error but got: $RESPONSE"
    return 1
  fi

  return 0
}

# Fonction pour tester tous les dashboards
test_all() {
  local passed=0
  local failed=0

  for cred in "${CREDENTIALS[@]}"; do
    IFS=':' read -r role email password <<< "$cred"
    if test_dashboard "$role" "$email" "$password"; then
      ((passed++))
    else
      ((failed++))
    fi
  done

  # Test unauthorized
  if test_unauthorized; then
    ((passed++))
  else
    ((failed++))
  fi

  # Summary
  print_header "Test Summary"
  echo -e "${GREEN}Passed: $passed${NC}"
  if [ $failed -gt 0 ]; then
    echo -e "${RED}Failed: $failed${NC}"
    return 1
  else
    echo -e "${GREEN}All tests passed! ✨${NC}"
    return 0
  fi
}

# Main
case "${1:-all}" in
  admin)
    test_dashboard "admin" "admin@cms.fr" "password123"
    ;;
  editor)
    test_dashboard "editeur" "editeur@cms.fr" "password123"
    ;;
  author)
    test_dashboard "auteur" "auteur@cms.fr" "password123"
    ;;
  reader)
    test_dashboard "lecteur" "lecteur@cms.fr" "password123"
    ;;
  unauthorized)
    test_unauthorized
    ;;
  all)
    test_all
    ;;
  *)
    echo "Usage: $0 [--admin|--editor|--author|--reader|--unauthorized|--all]"
    echo ""
    echo "Examples:"
    echo "  $0 admin          # Test admin dashboard"
    echo "  $0 editor         # Test editor dashboard"
    echo "  $0 author         # Test author dashboard"
    echo "  $0 reader         # Test reader dashboard"
    echo "  $0 unauthorized   # Test unauthorized access"
    echo "  $0 all            # Test all dashboards (default)"
    exit 1
    ;;
esac
