#!/bin/bash

# Documentation Maintenance Script
# Purpose: Automated maintenance and validation of project documentation
# Usage: ./maintain-documentation.sh [check|update|validate]

set -e

# Configuration
PROJECT_ROOT="/home/ankur/workspace/biz-dir"
DOCS_EXTERNAL_CONFIG="/home/ankur/biz-dir-configs"
BACKUP_DIR="$PROJECT_ROOT/backups/docs"
LOG_FILE="$PROJECT_ROOT/logs/documentation-maintenance.log"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging function
log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" | tee -a "$LOG_FILE"
}

# Create necessary directories
mkdir -p "$BACKUP_DIR" "$(dirname "$LOG_FILE")"

# Function to check documentation freshness
check_documentation_freshness() {
    echo -e "${BLUE}🔍 Checking documentation freshness...${NC}"
    
    local docs=(
        "$PROJECT_ROOT/CONFIGURATION_GUIDE.md"
        "$PROJECT_ROOT/PROJECT_SETUP_GUIDE.md"
        "$PROJECT_ROOT/mvp/tests/KNOWLEDGE_TRACKER.md"
        "$PROJECT_ROOT/mvp/tests/AI_TROUBLESHOOTING_TRACKER.md"
    )
    
    local outdated_docs=()
    local current_date=$(date +%s)
    local thirty_days=$((30 * 24 * 60 * 60))
    
    for doc in "${docs[@]}"; do
        if [[ -f "$doc" ]]; then
            local last_modified=$(stat -c %Y "$doc")
            local age=$((current_date - last_modified))
            
            if [[ $age -gt $thirty_days ]]; then
                outdated_docs+=("$doc")
                log "⚠️  Document outdated (>30 days): $doc"
            else
                log "✅ Document fresh: $doc"
            fi
        else
            log "❌ Missing document: $doc"
        fi
    done
    
    if [[ ${#outdated_docs[@]} -gt 0 ]]; then
        echo -e "${YELLOW}⚠️  ${#outdated_docs[@]} documents need review${NC}"
        return 1
    else
        echo -e "${GREEN}✅ All documentation is current${NC}"
        return 0
    fi
}

# Function to validate configuration setup
validate_configuration_setup() {
    echo -e "${BLUE}🔧 Validating configuration setup...${NC}"
    
    # Check external configuration directory
    if [[ ! -d "$DOCS_EXTERNAL_CONFIG" ]]; then
        log "❌ External configuration directory missing: $DOCS_EXTERNAL_CONFIG"
        echo -e "${RED}❌ External configuration directory not found${NC}"
        echo -e "${YELLOW}💡 Create with: mkdir -p $DOCS_EXTERNAL_CONFIG/{development,staging,production}${NC}"
        return 1
    fi
    
    # Check environment directories
    local environments=("development" "staging" "production")
    local missing_envs=()
    
    for env in "${environments[@]}"; do
        if [[ ! -d "$DOCS_EXTERNAL_CONFIG/$env" ]]; then
            missing_envs+=("$env")
        fi
    done
    
    if [[ ${#missing_envs[@]} -gt 0 ]]; then
        log "❌ Missing environment directories: ${missing_envs[*]}"
        echo -e "${RED}❌ Missing environment directories: ${missing_envs[*]}${NC}"
        return 1
    fi
    
    # Check for sensitive files in repository
    echo -e "${BLUE}🔒 Checking for sensitive files in repository...${NC}"
    cd "$PROJECT_ROOT"
    
    local sensitive_patterns=(
        "wp-config.php"
        "*.env"
        "*password*"
        "*secret*"
        "*key*"
        "*.pem"
        "*.key"
    )
    
    local found_sensitive=false
    for pattern in "${sensitive_patterns[@]}"; do
        if git ls-files | grep -q "$pattern"; then
            log "⚠️  Potentially sensitive file in repository: $pattern"
            found_sensitive=true
        fi
    done
    
    if [[ "$found_sensitive" == true ]]; then
        echo -e "${YELLOW}⚠️  Potential sensitive files found in repository${NC}"
        echo -e "${YELLOW}💡 Review and move to external configuration if needed${NC}"
    else
        echo -e "${GREEN}✅ No sensitive files detected in repository${NC}"
    fi
    
    log "✅ Configuration validation completed"
    return 0
}

# Function to update documentation timestamps
update_documentation_timestamps() {
    echo -e "${BLUE}📝 Updating documentation timestamps...${NC}"
    
    local current_date=$(date '+%B %d, %Y')
    local docs=(
        "$PROJECT_ROOT/CONFIGURATION_GUIDE.md"
        "$PROJECT_ROOT/PROJECT_SETUP_GUIDE.md"
    )
    
    for doc in "${docs[@]}"; do
        if [[ -f "$doc" ]]; then
            # Create backup
            cp "$doc" "$BACKUP_DIR/$(basename "$doc").backup.$(date +%Y%m%d-%H%M%S)"
            
            # Update timestamp
            sed -i "s/Last Updated.*$/Last Updated**: $current_date/" "$doc"
            sed -i "s/Next Review.*$/Next Review**: $(date -d '+30 days' '+%B %d, %Y')/" "$doc"
            
            log "✅ Updated timestamps in: $doc"
        fi
    done
    
    echo -e "${GREEN}✅ Documentation timestamps updated${NC}"
}

# Function to validate project dependencies
validate_project_dependencies() {
    echo -e "${BLUE}🔍 Validating project dependencies...${NC}"
    
    cd "$PROJECT_ROOT/mvp"
    
    # Check Composer dependencies
    if [[ -f "composer.json" ]]; then
        composer validate --strict --no-check-publish
        if [[ $? -eq 0 ]]; then
            log "✅ Composer configuration valid"
        else
            log "❌ Composer configuration issues detected"
            return 1
        fi
    fi
    
    # Check for outdated dependencies
    composer outdated --direct --strict > /tmp/outdated_deps.txt 2>&1 || true
    if [[ -s /tmp/outdated_deps.txt ]]; then
        log "⚠️  Outdated dependencies detected"
        echo -e "${YELLOW}⚠️  Some dependencies may be outdated${NC}"
        cat /tmp/outdated_deps.txt
    else
        log "✅ All dependencies are current"
        echo -e "${GREEN}✅ All dependencies are current${NC}"
    fi
    
    rm -f /tmp/outdated_deps.txt
    return 0
}

# Function to run comprehensive validation
run_comprehensive_check() {
    echo -e "${BLUE}🔍 Running comprehensive documentation and configuration check...${NC}"
    log "Starting comprehensive validation"
    
    local checks_passed=0
    local total_checks=4
    
    # Run all validation checks
    check_documentation_freshness && ((checks_passed++)) || true
    validate_configuration_setup && ((checks_passed++)) || true
    validate_project_dependencies && ((checks_passed++)) || true
    
    # Additional: Check git status
    cd "$PROJECT_ROOT"
    if git status --porcelain | grep -q .; then
        log "⚠️  Uncommitted changes detected"
        echo -e "${YELLOW}⚠️  Uncommitted changes detected${NC}"
        git status --short
    else
        log "✅ Repository is clean"
        echo -e "${GREEN}✅ Repository is clean${NC}"
        ((checks_passed++))
    fi
    
    # Summary
    echo -e "\n${BLUE}📊 Validation Summary:${NC}"
    echo -e "Checks passed: ${GREEN}$checks_passed${NC}/$total_checks"
    
    if [[ $checks_passed -eq $total_checks ]]; then
        echo -e "${GREEN}🎉 All checks passed!${NC}"
        log "✅ All validation checks passed"
        return 0
    else
        echo -e "${YELLOW}⚠️  Some checks need attention${NC}"
        log "⚠️  $((total_checks - checks_passed)) checks failed"
        return 1
    fi
}

# Function to display help
show_help() {
    cat << EOF
Documentation Maintenance Script

Usage: $0 [COMMAND]

Commands:
    check       Check documentation freshness and configuration
    update      Update documentation timestamps
    validate    Validate project dependencies and configuration
    full        Run comprehensive check (default)
    help        Show this help message

Examples:
    $0 check           # Check documentation status
    $0 update          # Update timestamps
    $0 validate        # Validate dependencies
    $0 full            # Run all checks

Files managed:
    - CONFIGURATION_GUIDE.md
    - PROJECT_SETUP_GUIDE.md
    - mvp/tests/KNOWLEDGE_TRACKER.md
    - mvp/tests/AI_TROUBLESHOOTING_TRACKER.md

Logs: $LOG_FILE
Backups: $BACKUP_DIR

EOF
}

# Main execution
main() {
    local command="${1:-full}"
    
    log "Documentation maintenance started with command: $command"
    
    case "$command" in
        "check")
            check_documentation_freshness
            ;;
        "update")
            update_documentation_timestamps
            ;;
        "validate")
            validate_configuration_setup
            validate_project_dependencies
            ;;
        "full")
            run_comprehensive_check
            ;;
        "help"|"--help"|"-h")
            show_help
            ;;
        *)
            echo -e "${RED}❌ Unknown command: $command${NC}"
            show_help
            exit 1
            ;;
    esac
}

# Execute main function with all arguments
main "$@"
