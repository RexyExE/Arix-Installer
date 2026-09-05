#!/usr/bin/env bash
# ==============================================================================
#  Arix Theme & Pterodactyl Panel Master Management CLI
#  Version: 2.1.0 (Production Stable)
#  Supported OS: Ubuntu 20.04/22.04/24.04, Debian 11/12, AlmaLinux/Rocky 8/9, RHEL
# ==============================================================================

set -o pipefail

# --- Color Scheme & Formatting ---
C_RESET='\033[0m'
C_BOLD='\033[1m'
C_DIM='\033[2m'
C_RED='\033[38;5;196m'
C_GREEN='\033[38;5;46m'
C_YELLOW='\033[38;5;226m'
C_BLUE='\033[38;5;45m'
C_PURPLE='\033[38;5;129m'
C_CYAN='\033[38;5;51m'
C_WHITE='\033[38;5;231m'
C_BG_DARK='\033[48;5;234m'
C_GRAY='\033[38;5;244m'

PANEL_DIR="/var/www/pterodactyl"
BACKUP_DIR="/var/backups/pterodactyl"
THEME_VERSION="v2.0.8"
SCRIPT_VERSION="2.1.0"
LOG_FILE="/var/log/arix-manager.log"

# Create backup and log directory if possible
mkdir -p "$BACKUP_DIR" 2>/dev/null || true
touch "$LOG_FILE" 2>/dev/null || true

# --- Logging and Output Helpers ---
log() {
    local level="$1"
    shift
    local msg="$*"
    local timestamp
    timestamp="$(date '+%Y-%m-%d %H:%M:%S')"
    echo -e "${timestamp} [${level}] ${msg}" >> "$LOG_FILE" 2>/dev/null || true
}

print_info() {
    echo -e " ${C_CYAN}ℹ${C_RESET}  ${C_WHITE}$1${C_RESET}"
    log "INFO" "$1"
}

print_success() {
    echo -e " ${C_GREEN}✔${C_RESET}  ${C_GREEN}${C_BOLD}$1${C_RESET}"
    log "SUCCESS" "$1"
}

print_warn() {
    echo -e " ${C_YELLOW}⚠${C_RESET}  ${C_YELLOW}$1${C_RESET}"
    log "WARN" "$1"
}

print_error() {
    echo -e " ${C_RED}✖${C_RESET}  ${C_RED}${C_BOLD}$1${C_RESET}"
    log "ERROR" "$1"
}

print_step() {
    echo -e "\n ${C_PURPLE}▶${C_RESET} ${C_BOLD}${C_WHITE}$1${C_RESET}..."
    log "STEP" "$1"
}

# --- System & Environment Detection ---
check_root() {
    if [[ $EUID -ne 0 ]]; then
        print_error "This script must be run as root (use: sudo bash arix-manager.sh)"
        exit 1
    fi
}

detect_webuser() {
    if id "www-data" &>/dev/null; then
        echo "www-data:www-data"
    elif id "nginx" &>/dev/null; then
        echo "nginx:nginx"
    elif id "apache" &>/dev/null; then
        echo "apache:apache"
    elif id "caddy" &>/dev/null; then
        echo "caddy:caddy"
    else
        echo "www-data:www-data"
    fi
}

detect_distro() {
    if [ -f /etc/os-release ]; then
        . /etc/os-release
        echo "$ID"
    else
        echo "unknown"
    fi
}

detect_panel() {
    if [ -f "${PANEL_DIR}/artisan" ] && [ -f "${PANEL_DIR}/.env" ]; then
        return 0
    fi
    return 1
}

# Load database credentials from Panel .env
load_db_credentials() {
    if [ ! -f "${PANEL_DIR}/.env" ]; then
        return 1
    fi

    DB_CONNECTION=$(grep "^DB_CONNECTION=" "${PANEL_DIR}/.env" | cut -d '=' -f2- | tr -d '"' | tr -d "'")
    DB_HOST=$(grep "^DB_HOST=" "${PANEL_DIR}/.env" | cut -d '=' -f2- | tr -d '"' | tr -d "'")
    DB_PORT=$(grep "^DB_PORT=" "${PANEL_DIR}/.env" | cut -d '=' -f2- | tr -d '"' | tr -d "'")
    DB_DATABASE=$(grep "^DB_DATABASE=" "${PANEL_DIR}/.env" | cut -d '=' -f2- | tr -d '"' | tr -d "'")
    DB_USERNAME=$(grep "^DB_USERNAME=" "${PANEL_DIR}/.env" | cut -d '=' -f2- | tr -d '"' | tr -d "'")
    DB_PASSWORD=$(grep "^DB_PASSWORD=" "${PANEL_DIR}/.env" | cut -d '=' -f2- | tr -d '"' | tr -d "'")

    DB_HOST="${DB_HOST:-127.0.0.1}"
    DB_PORT="${DB_PORT:-3306}"
    return 0
}

# Wait for user input prompt
press_enter() {
    echo -e ""
    read -rp " Press [Enter] to return to the menu..." dummy
}

# --- Header & Banner ---
show_banner() {
    clear
    echo -e "${C_CYAN}"
    cat << "EOF"
   █████╗ ██████╗ ██╗██╗  ██╗    ███╗   ███╗ █████╗ ███╗   ██╗ █████╗  ██████╗ ███████╗██████╗ 
  ██╔══██╗██╔══██╗██║╚██╗██╔╝    ████╗ ████║██╔══██╗████╗  ██║██╔══██╗██╔════╝ ██╔════╝██╔══██╗
  ███████║██████╔╝██║ ╚███╔╝     ██╔████╔██║███████║██╔██╗ ██║███████║██║  ███╗█████╗  ██████╔╝
  ██╔══██║██╔══██╗██║ ██╔██╗     ██║╚██╔╝██║██╔══██║██║╚██╗██║██╔══██║██║   ██║██╔══╝  ██╔══██╗
  ██║  ██║██║  ██║██║██╔╝ ██╗    ██║ ╚═╝ ██║██║  ██║██║ ╚████║██║  ██║╚██████╔╝███████╗██║  ██║
  ╚═╝  ╚═╝╚═╝  ╚═╝╚═╝╚═╝  ╚═╝    ╚═╝     ╚═╝╚═╝  ╚═╝╚═╝  ╚═══╝╚═╝  ╚═╝ ╚═════╝ ╚══════╝╚═╝  ╚═╝
EOF
    echo -e "${C_RESET}"
    echo -e " ${C_BOLD}${C_WHITE}Arix Theme ${THEME_VERSION} & Pterodactyl Multi-Management Suite${C_RESET} ${C_GRAY}(v${SCRIPT_VERSION})${C_RESET}"
    echo -e " ${C_DIM}─────────────────────────────────────────────────────────────────────────────${C_RESET}"
    
    if detect_panel; then
        local webuser
        webuser=$(detect_webuser)
        echo -e " Panel Status:  ${C_GREEN}● Detected${C_RESET} (${PANEL_DIR})"
        echo -e " Web User:      ${C_CYAN}${webuser}${C_RESET} | Backup Dir: ${C_CYAN}${BACKUP_DIR}${C_RESET}"
    else
        echo -e " Panel Status:  ${C_RED}○ Not Detected${C_RESET} (${PANEL_DIR} not found)"
    fi
    echo -e " ${C_DIM}─────────────────────────────────────────────────────────────────────────────${C_RESET}\n"
}

# --- Permissions Fixer ---
fix_permissions() {
    local webuser
    webuser=$(detect_webuser)
    print_step "Setting correct file ownership and permissions (${webuser})"
    
    if [ -d "$PANEL_DIR" ]; then
        chown -R "$webuser" "${PANEL_DIR}"
        chmod -R 755 "${PANEL_DIR}/storage" "${PANEL_DIR}/bootstrap/cache" 2>/dev/null || true
        print_success "Permissions set to ${webuser} on ${PANEL_DIR}"
    fi
}

# --- Cache & Optimization Clearer ---
clear_panel_caches() {
    if [ -f "${PANEL_DIR}/artisan" ]; then
        print_step "Flushing Laravel application, route, view, and config caches"
        (
            cd "$PANEL_DIR" || exit 1
            php artisan optimize:clear >/dev/null 2>&1 || true
            php artisan view:clear >/dev/null 2>&1 || true
            php artisan config:clear >/dev/null 2>&1 || true
            php artisan route:clear >/dev/null 2>&1 || true
        )
        print_success "All application caches cleared successfully"
    fi
}

# ==============================================================================
# SECTION 1: BACKUP & RESTORE ENGINE
# ==============================================================================

backup_database() {
    print_step "Running database backup"
    if ! load_db_credentials; then
        print_error "Could not read database configuration from ${PANEL_DIR}/.env"
        return 1
    fi

    local timestamp
    timestamp="$(date +%F_%H-%M-%S)"
    local target_file="${BACKUP_DIR}/db_backup_${timestamp}.sql.gz"

    if ! command -v mysqldump &>/dev/null; then
        print_error "mysqldump command not found on this system. Install mariadb-client / mysql-client."
        return 1
    fi

    print_info "Dumping database '${DB_DATABASE}' from host ${DB_HOST}:${DB_PORT}..."
    if [ -n "$DB_PASSWORD" ]; then
        mysqldump -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" 2>/dev/null | gzip > "$target_file"
    else
        mysqldump -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" "$DB_DATABASE" 2>/dev/null | gzip > "$target_file"
    fi

    if [ -s "$target_file" ]; then
        local size
        size=$(du -h "$target_file" | awk '{print $1}')
        print_success "Database backup created: ${target_file} (${size})"
        return 0
    else
        rm -f "$target_file"
        print_error "Database dump failed. Please check MySQL credentials and permissions."
        return 1
    fi
}

backup_theme_only() {
    print_step "Creating Theme-Only Backup (Arix configuration and customized templates)"
    if ! detect_panel; then
        print_error "Pterodactyl panel not detected at ${PANEL_DIR}"
        return 1
    fi

    local timestamp
    timestamp="$(date +%F_%H-%M-%S)"
    local target_file="${BACKUP_DIR}/arix_theme_backup_${timestamp}.tar.gz"

    tar -czf "$target_file" -C "$PANEL_DIR" \
        app/Http/Controllers/Admin/Arix \
        app/Console/Commands/Arix.php \
        app/Console/Commands/ArixFix.php \
        app/Console/Commands/ArixLang.php \
        config/arixTheme.php \
        resources/views/admin/arix \
        resources/views/components/arix \
        resources/views/layouts/arix.blade.php \
        public/arix \
        2>/dev/null || true

    if [ -s "$target_file" ]; then
        local size
        size=$(du -h "$target_file" | awk '{print $1}')
        print_success "Arix Theme backup created: ${target_file} (${size})"
        return 0
    else
        print_error "Failed to create theme backup."
        return 1
    fi
}

backup_panel_files_only() {
    print_step "Creating Panel Files Backup (Excluding node_modules, logs, and temp sessions)"
    if ! detect_panel; then
        print_error "Pterodactyl panel not detected at ${PANEL_DIR}"
        return 1
    fi

    local timestamp
    timestamp="$(date +%F_%H-%M-%S)"
    local target_file="${BACKUP_DIR}/pterodactyl_files_backup_${timestamp}.tar.gz"

    tar --exclude='node_modules' \
        --exclude='storage/logs/*.log' \
        --exclude='storage/framework/cache/*' \
        --exclude='storage/framework/sessions/*' \
        --exclude='storage/framework/views/*' \
        -czf "$target_file" -C "/var/www" "pterodactyl"

    if [ -s "$target_file" ]; then
        local size
        size=$(du -h "$target_file" | awk '{print $1}')
        print_success "Panel files backup created: ${target_file} (${size})"
        return 0
    else
        print_error "Failed to create panel files backup."
        return 1
    fi
}

backup_full_system() {
    print_step "Starting Full Sectionized Backup Bundle (Panel Files + MySQL Database)"
    local timestamp
    timestamp="$(date +%F_%H-%M-%S)"
    local bundle_dir="${BACKUP_DIR}/full_bundle_${timestamp}"
    mkdir -p "$bundle_dir"

    # 1. Database
    load_db_credentials
    local db_file="${bundle_dir}/database_${DB_DATABASE}.sql.gz"
    if [ -n "$DB_PASSWORD" ]; then
        mysqldump -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" 2>/dev/null | gzip > "$db_file"
    else
        mysqldump -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" "$DB_DATABASE" 2>/dev/null | gzip > "$db_file"
    fi

    # 2. Panel Files
    local files_file="${bundle_dir}/panel_files.tar.gz"
    tar --exclude='node_modules' \
        --exclude='storage/logs/*.log' \
        --exclude='storage/framework/cache/*' \
        --exclude='storage/framework/views/*' \
        -czf "$files_file" -C "/var/www" "pterodactyl"

    # 3. Environment Manifest
    cat << EOF > "${bundle_dir}/manifest.json"
{
  "timestamp": "${timestamp}",
  "theme_version": "${THEME_VERSION}",
  "script_version": "${SCRIPT_VERSION}",
  "database": "${DB_DATABASE}",
  "php_version": "$(php -r 'echo PHP_VERSION;' 2>/dev/null || echo 'unknown')"
}
EOF

    # Pack into single master archive
    local final_archive="${BACKUP_DIR}/full_system_sectionized_${timestamp}.tar.gz"
    tar -czf "$final_archive" -C "$BACKUP_DIR" "full_bundle_${timestamp}"
    rm -rf "$bundle_dir"

    if [ -s "$final_archive" ]; then
        local size
        size=$(du -h "$final_archive" | awk '{print $1}')
        print_success "Full system bundle created: ${final_archive} (${size})"
        return 0
    else
        print_error "Failed to package full system bundle."
        return 1
    fi
}

restore_backup_menu() {
    clear
    show_banner
    echo -e " ${C_BOLD}${C_YELLOW}RESTORE BACKUP ARCHIVE${C_RESET}\n"

    local backups=( $(ls -1t "${BACKUP_DIR}"/*.tar.gz "${BACKUP_DIR}"/*.sql.gz 2>/dev/null || true) )
    if [ ${#backups[@]} -eq 0 ]; then
        print_warn "No backups found in ${BACKUP_DIR}"
        press_enter
        return
    fi

    echo -e " Available Backups:"
    local idx=1
    for b in "${backups[@]}"; do
        local bname
        bname=$(basename "$b")
        local bsize
        bsize=$(du -h "$b" | awk '{print $1}')
        echo -e "  ${C_CYAN}[${idx}]${C_RESET} ${bname} ${C_GRAY}(${bsize})${C_RESET}"
        idx=$((idx + 1))
    done
    echo -e "  ${C_RED}[0]${C_RESET} Cancel"

    echo ""
    read -rp " Select backup number to restore: " choice
    if [ "$choice" = "0" ] || [ -z "$choice" ]; then
        return
    fi

    if ! [[ "$choice" =~ ^[0-9]+$ ]] || [ "$choice" -lt 1 ] || [ "$choice" -gt ${#backups[@]} ]; then
        print_error "Invalid selection."
        press_enter
        return
    fi

    local selected="${backups[$((choice - 1))]}"
    print_warn "Selected backup: $(basename "$selected")"
    read -rp " Are you sure you want to restore this? This will overwrite active files! (y/N): " confirm
    if [[ ! "$confirm" =~ ^[yY]$ ]]; then
        print_info "Restore cancelled."
        press_enter
        return
    fi

    if [[ "$selected" == *.sql.gz ]]; then
        load_db_credentials
        print_step "Restoring database from gzip dump"
        if [ -n "$DB_PASSWORD" ]; then
            gunzip -c "$selected" | mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE"
        else
            gunzip -c "$selected" | mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" "$DB_DATABASE"
        fi
        print_success "Database restored successfully."
    elif [[ "$selected" == *full_system_sectionized* ]]; then
        print_step "Restoring Full System Sectionized Bundle"
        local tmp_restore="/tmp/arix_restore_$$"
        mkdir -p "$tmp_restore"
        tar -xzf "$selected" -C "$tmp_restore"
        local inner_dir
        inner_dir=$(find "$tmp_restore" -mindepth 1 -maxdepth 1 -type d | head -n 1)

        if [ -d "$inner_dir" ]; then
            # 1. Restore Database
            local db_dump
            db_dump=$(find "$inner_dir" -name "*.sql.gz" | head -n 1)
            if [ -f "$db_dump" ]; then
                load_db_credentials
                print_info "Restoring bundled database dump..."
                if [ -n "$DB_PASSWORD" ]; then
                    gunzip -c "$db_dump" | mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE"
                else
                    gunzip -c "$db_dump" | mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" "$DB_DATABASE"
                fi
            fi

            # 2. Restore Files
            local files_dump
            files_dump=$(find "$inner_dir" -name "panel_files.tar.gz" | head -n 1)
            if [ -f "$files_dump" ]; then
                print_info "Restoring panel files to /var/www..."
                tar -xzf "$files_dump" -C "/var/www"
            fi
        fi
        rm -rf "$tmp_restore"
        fix_permissions
        clear_panel_caches
        print_success "Full system bundle restore complete!"
    elif [[ "$selected" == *pterodactyl_files_backup* ]]; then
        print_step "Restoring Panel Files to /var/www"
        tar -xzf "$selected" -C "/var/www"
        fix_permissions
        clear_panel_caches
        print_success "Panel files restored successfully!"
    elif [[ "$selected" == *arix_theme_backup* ]]; then
        print_step "Restoring Arix Theme files to ${PANEL_DIR}"
        tar -xzf "$selected" -C "$PANEL_DIR"
        fix_permissions
        clear_panel_caches
        print_success "Theme files restored successfully!"
    fi

    press_enter
}

# ==============================================================================
# SECTION 2: ARIX THEME MANAGEMENT
# ==============================================================================

install_theme() {
    print_step "Installing Arix Theme ${THEME_VERSION} to Pterodactyl"
    if ! detect_panel; then
        print_error "Pterodactyl Panel not found at ${PANEL_DIR}. Please install Pterodactyl first."
        press_enter
        return 1
    fi

    # Locate source directory
    local script_dir
    script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
    local theme_source=""

    if [ -d "${script_dir}/pterodactyl/arix/v2.0.8" ]; then
        theme_source="${script_dir}/pterodactyl/arix/v2.0.8"
    elif [ -d "${script_dir}/pterodactyl" ] && [ -f "${script_dir}/pterodactyl/config/arixTheme.php" ]; then
        theme_source="${script_dir}/pterodactyl"
    elif [ -d "/tmp/arix/v2.0.8" ]; then
        theme_source="/tmp/arix/v2.0.8"
    fi

    if [ -z "$theme_source" ] || [ ! -d "$theme_source" ]; then
        print_warn "Local theme files not found automatically."
        echo -e " Enter absolute path to extracted Arix Theme folder (containing app/, resources/, etc.):"
        read -rp " Path: " user_path
        if [ -d "$user_path" ]; then
            theme_source="$user_path"
        else
            print_error "Directory does not exist: $user_path"
            press_enter
            return 1
        fi
    fi

    # Automated safety backup before installation
    print_info "Creating automatic pre-install backup..."
    backup_theme_only 2>/dev/null || true

    print_info "Deploying theme files from: ${theme_source} -> ${PANEL_DIR}"
    cp -rf "${theme_source}/"* "${PANEL_DIR}/"

    print_step "Running Theme Database Migrations"
    (
        cd "$PANEL_DIR" || exit 1
        php artisan migrate --force
    )

    print_step "Running Arix Theme Setup & Seeding"
    (
        cd "$PANEL_DIR" || exit 1
        if php artisan list | grep -q "arix:fix"; then
            php artisan arix:fix
        fi
        if php artisan list | grep -q "arix"; then
            php artisan arix install
        fi
    )

    # Rebuild assets if yarn is available or ask
    echo ""
    read -rp " Would you like to build frontend production assets now (yarn build:production)? (y/N): " do_build
    if [[ "$do_build" =~ ^[yY]$ ]]; then
        rebuild_theme
    fi

    fix_permissions
    clear_panel_caches

    print_success "Arix Theme ${THEME_VERSION} successfully installed!"
    press_enter
}

remove_theme() {
    print_step "Removing Arix Theme from Pterodactyl Panel"
    if ! detect_panel; then
        print_error "Pterodactyl Panel not found at ${PANEL_DIR}"
        press_enter
        return 1
    fi

    print_warn "This will remove Arix Theme views, configs, commands, and restore default Pterodactyl assets."
    read -rp " Are you sure you want to proceed? (y/N): " confirm
    if [[ ! "$confirm" =~ ^[yY]$ ]]; then
        print_info "Removal cancelled."
        press_enter
        return
    fi

    backup_theme_only

    print_step "Cleaning up Arix files"
    rm -rf "${PANEL_DIR}/public/arix"
    rm -rf "${PANEL_DIR}/resources/views/admin/arix"
    rm -rf "${PANEL_DIR}/resources/views/components/arix"
    rm -f "${PANEL_DIR}/resources/views/layouts/arix.blade.php"
    rm -rf "${PANEL_DIR}/app/Http/Controllers/Admin/Arix"
    rm -f "${PANEL_DIR}/app/Console/Commands/Arix.php"
    rm -f "${PANEL_DIR}/app/Console/Commands/arix.php"
    rm -f "${PANEL_DIR}/app/Console/Commands/ArixFix.php"
    rm -f "${PANEL_DIR}/app/Console/Commands/ArixLang.php"
    rm -f "${PANEL_DIR}/config/arixTheme.php"

    print_step "Downloading clean stock Pterodactyl panel files"
    local tmp_stock="/tmp/ptero_stock_$$"
    mkdir -p "$tmp_stock"
    local panel_url="https://github.com/pterodactyl/panel/releases/latest/download/panel.tar.gz"
    
    print_info "Fetching stock release from GitHub..."
    if curl -sSL "$panel_url" | tar -xz -C "$tmp_stock"; then
        cp -rf "${tmp_stock}/resources" "${PANEL_DIR}/"
        cp -rf "${tmp_stock}/public" "${PANEL_DIR}/"
        cp -rf "${tmp_stock}/routes" "${PANEL_DIR}/"
        cp -rf "${tmp_stock}/app" "${PANEL_DIR}/"
        print_success "Stock files restored."
    else
        print_warn "Could not download stock panel files automatically. Local Arix artifacts were deleted."
    fi
    rm -rf "$tmp_stock"

    fix_permissions
    clear_panel_caches

    print_success "Arix Theme removed and default panel restored."
    press_enter
}

rebuild_theme() {
    print_step "Rebuilding Theme & Frontend Assets"
    if ! detect_panel; then
        print_error "Pterodactyl Panel not found at ${PANEL_DIR}"
        press_enter
        return 1
    fi

    cd "$PANEL_DIR" || exit 1

    # Check for node and yarn
    if ! command -v node &>/dev/null; then
        print_warn "Node.js is not installed. Installing Node.js LTS..."
        curl -fsSL https://deb.nodesource.com/setup_18.x | bash - 2>/dev/null || true
        apt-get install -y nodejs 2>/dev/null || yum install -y nodejs 2>/dev/null || true
    fi

    if ! command -v yarn &>/dev/null; then
        print_warn "Yarn not found. Installing yarn globally..."
        npm install -g yarn 2>/dev/null || true
    fi

    print_info "Building production assets (OpenSSL legacy provider enabled)..."
    export NODE_OPTIONS="--openssl-legacy-provider"

    yarn install --frozen-lockfile 2>/dev/null || yarn install
    yarn build:production

    fix_permissions
    clear_panel_caches

    print_success "Production assets built and compiled successfully!"
}

repair_theme() {
    print_step "Running Arix Theme Self-Healing Repair Engine"
    if ! detect_panel; then
        print_error "Pterodactyl Panel not found at ${PANEL_DIR}"
        press_enter
        return 1
    fi

    cd "$PANEL_DIR" || exit 1

    print_info "1. Syncing database migrations..."
    php artisan migrate --force

    print_info "2. Executing arix:fix self-healing artisan routine..."
    if php artisan list | grep -q "arix:fix"; then
        php artisan arix:fix
    elif php artisan list | grep -q "arix"; then
        php artisan arix fix
    fi

    print_info "3. Clearing view, config, route, and application caches..."
    php artisan optimize:clear

    print_info "4. Auditing file permissions..."
    fix_permissions

    print_success "Arix Theme repair completed successfully!"
    press_enter
}

# ==============================================================================
# SECTION 3: PTERODACTYL PANEL MANAGEMENT
# ==============================================================================

install_pterodactyl() {
    print_step "Fresh Pterodactyl Panel Installation"
    if detect_panel; then
        print_warn "An existing Pterodactyl installation was detected at ${PANEL_DIR}."
        read -rp " Are you sure you want to overwrite it? (y/N): " confirm
        if [[ ! "$confirm" =~ ^[yY]$ ]]; then
            print_info "Installation aborted."
            press_enter
            return
        fi
    fi

    mkdir -p "$PANEL_DIR"
    cd "$PANEL_DIR" || exit 1

    print_info "Downloading latest Pterodactyl release..."
    curl -Lo panel.tar.gz https://github.com/pterodactyl/panel/releases/latest/download/panel.tar.gz
    tar -xzvf panel.tar.gz
    chmod -R 755 storage/* bootstrap/cache/

    print_info "Setting up environment configuration..."
    if [ ! -f .env ]; then
        cp .env.example .env
        composer install --no-dev --optimize-autoloader
        php artisan key:generate --force
    fi

    print_info "Configuring database and setup..."
    php artisan p:environment:setup
    php artisan p:environment:database
    php artisan migrate --seed --force
    php artisan p:user:make

    fix_permissions
    print_success "Pterodactyl Panel installed successfully at ${PANEL_DIR}!"
    press_enter
}

repair_pterodactyl() {
    print_step "Repairing Pterodactyl Panel Core"
    if ! detect_panel; then
        print_error "Pterodactyl Panel not found at ${PANEL_DIR}"
        press_enter
        return 1
    fi

    cd "$PANEL_DIR" || exit 1

    print_info "1. Updating Composer dependencies..."
    composer install --no-dev --optimize-autoloader 2>/dev/null || composer install --no-dev

    print_info "2. Running pending migrations..."
    php artisan migrate --force

    print_info "3. Clearing and rebuilding configuration caches..."
    php artisan optimize:clear
    php artisan view:clear
    php artisan config:clear
    php artisan route:clear

    print_info "4. Correcting directory permissions..."
    fix_permissions

    print_info "5. Restarting queue worker..."
    php artisan queue:restart 2>/dev/null || true

    print_success "Pterodactyl core repair finished!"
    press_enter
}

rebuild_pterodactyl() {
    print_step "Rebuilding Pterodactyl Panel"
    rebuild_theme
    repair_pterodactyl
}

remove_pterodactyl() {
    print_step "Uninstalling & Removing Pterodactyl Panel Completely"
    print_warn "CAUTION: This will delete ${PANEL_DIR} and optionally the database."
    read -rp " Are you absolutely sure? Type 'DELETE' to confirm: " confirm
    if [ "$confirm" != "DELETE" ]; then
        print_info "Panel removal aborted."
        press_enter
        return
    fi

    print_info "Creating emergency backup before removal..."
    backup_full_system 2>/dev/null || true

    print_step "Stopping Pterodactyl services (pteroq, web server, etc.)"
    systemctl stop pteroq 2>/dev/null || true

    print_info "Removing panel directory: ${PANEL_DIR}"
    rm -rf "$PANEL_DIR"

    read -rp " Do you also want to drop the Pterodactyl MySQL database? (y/N): " drop_db
    if [[ "$drop_db" =~ ^[yY]$ ]]; then
        load_db_credentials
        if [ -n "$DB_DATABASE" ]; then
            print_warn "Dropping database: ${DB_DATABASE}"
            if [ -n "$DB_PASSWORD" ]; then
                mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" -e "DROP DATABASE IF EXISTS \`${DB_DATABASE}\`;" 2>/dev/null || true
            else
                mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -e "DROP DATABASE IF EXISTS \`${DB_DATABASE}\`;" 2>/dev/null || true
            fi
            print_success "Database dropped."
        fi
    fi

    print_success "Pterodactyl Panel removed from the system."
    press_enter
}

# ==============================================================================
# SECTION 4: SYSTEM DOCTOR & INTEGRITY AUDIT
# ==============================================================================

system_doctor() {
    clear
    show_banner
    echo -e " ${C_BOLD}${C_CYAN}SYSTEM HEALTH & INTEGRITY AUDIT${C_RESET}\n"

    # 1. OS & Architecture
    echo -e " ${C_BOLD}1. System Environment:${C_RESET}"
    echo -e "    OS:          $(cat /etc/os-release 2>/dev/null | grep PRETTY_NAME | cut -d= -f2 | tr -d '\"' || uname -s)"
    echo -e "    Kernel:      $(uname -r)"
    echo -e "    Memory:      $(free -m | awk '/Mem:/ {print $3 "MB used / " $2 "MB total"}')"
    echo -e "    Disk:        $(df -h / | awk 'NR==2 {print $3 " used / " $2 " total (" $5 ")"}')"

    # 2. Runtimes
    echo -e "\n ${C_BOLD}2. Runtimes & Packages:${C_RESET}"
    if command -v php &>/dev/null; then
        echo -e "    PHP:         ${C_GREEN}$(php -r 'echo PHP_VERSION;')${C_RESET}"
    else
        echo -e "    PHP:         ${C_RED}Not Installed${C_RESET}"
    fi

    if command -v node &>/dev/null; then
        echo -e "    Node.js:     ${C_GREEN}$(node -v)${C_RESET}"
    else
        echo -e "    Node.js:     ${C_YELLOW}Not Installed${C_RESET}"
    fi

    if command -v yarn &>/dev/null; then
        echo -e "    Yarn:        ${C_GREEN}$(yarn -v)${C_RESET}"
    else
        echo -e "    Yarn:        ${C_YELLOW}Not Installed${C_RESET}"
    fi

    if command -v composer &>/dev/null; then
        echo -e "    Composer:    ${C_GREEN}$(composer --version --no-ansi 2>/dev/null | awk '{print $3}')${C_RESET}"
    else
        echo -e "    Composer:    ${C_YELLOW}Not Installed${C_RESET}"
    fi

    # 3. Panel Checks
    echo -e "\n ${C_BOLD}3. Pterodactyl Panel Audit:${C_RESET}"
    if detect_panel; then
        echo -e "    Files:       ${C_GREEN}Found at ${PANEL_DIR}${C_RESET}"
        if load_db_credentials; then
            echo -e "    Database:    ${C_GREEN}Configured (${DB_USERNAME}@${DB_HOST}/${DB_DATABASE})${C_RESET}"
            # Test DB connection
            local db_test
            if [ -n "$DB_PASSWORD" ]; then
                db_test=$(mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" -e "SELECT 1;" 2>&1 || true)
            else
                db_test=$(mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -e "SELECT 1;" 2>&1 || true)
            fi
            if [[ "$db_test" == *"1"* ]]; then
                echo -e "    DB Status:   ${C_GREEN}Online & Reachable${C_RESET}"
            else
                echo -e "    DB Status:   ${C_RED}Connection Failed!${C_RESET}"
            fi
        else
            echo -e "    Database:    ${C_YELLOW}Could not parse .env${C_RESET}"
        fi
    else
        echo -e "    Files:       ${C_RED}Not Found at ${PANEL_DIR}${C_RESET}"
    fi

    # 4. Arix Theme Checks
    echo -e "\n ${C_BOLD}4. Arix Theme Integrity:${C_RESET}"
    if [ -f "${PANEL_DIR}/config/arixTheme.php" ]; then
        echo -e "    Config:      ${C_GREEN}config/arixTheme.php Present${C_RESET}"
    else
        echo -e "    Config:      ${C_YELLOW}config/arixTheme.php Missing${C_RESET}"
    fi

    if [ -d "${PANEL_DIR}/public/arix" ]; then
        echo -e "    Assets:      ${C_GREEN}public/arix/ Present${C_RESET}"
    else
        echo -e "    Assets:      ${C_YELLOW}public/arix/ Missing${C_RESET}"
    fi

    if [ -f "${PANEL_DIR}/app/Console/Commands/ArixFix.php" ]; then
        echo -e "    Self-Heal:   ${C_GREEN}Artisan arix:fix available${C_RESET}"
    else
        echo -e "    Self-Heal:   ${C_YELLOW}Artisan arix:fix missing${C_RESET}"
    fi

    echo ""
    press_enter
}

# ==============================================================================
# MAIN INTERACTIVE MENU
# ==============================================================================

main_menu() {
    check_root

    while true; do
        show_banner
        echo -e " ${C_BOLD}${C_WHITE}SELECT AN OPTION:${C_RESET}\n"

        echo -e "  ${C_BOLD}${C_PURPLE}[ ARIX THEME MANAGEMENT ]${C_RESET}"
        echo -e "   ${C_CYAN}1)${C_RESET} Install Arix Theme"
        echo -e "   ${C_CYAN}2)${C_RESET} Rebuild Arix Theme Assets (Production)"
        echo -e "   ${C_CYAN}3)${C_RESET} Repair Arix Theme (Self-Healing Doctor)"
        echo -e "   ${C_CYAN}4)${C_RESET} Remove Arix Theme (Restore Stock Panel)\n"

        echo -e "  ${C_BOLD}${C_BLUE}[ PTERODACTYL PANEL MANAGEMENT ]${C_RESET}"
        echo -e "   ${C_CYAN}5)${C_RESET} Install Pterodactyl Panel (Fresh)"
        echo -e "   ${C_CYAN}6)${C_RESET} Rebuild Pterodactyl Panel (Yarn & Deps)"
        echo -e "   ${C_CYAN}7)${C_RESET} Repair Pterodactyl Panel (Migrations & Cache)"
        echo -e "   ${C_CYAN}8)${C_RESET} Remove Pterodactyl Panel Completely\n"

        echo -e "  ${C_BOLD}${C_GREEN}[ SECTIONIZED BACKUPS & RESTORE ]${C_RESET}"
        echo -e "   ${C_CYAN}9)${C_RESET}  Backup: Theme Only"
        echo -e "   ${C_CYAN}10)${C_RESET} Backup: Panel Files Only"
        echo -e "   ${C_CYAN}11)${C_RESET} Backup: MySQL Database Only"
        echo -e "   ${C_CYAN}12)${C_RESET} Backup: Full Sectionized System Bundle"
        echo -e "   ${C_CYAN}13)${C_RESET} Restore a Backup Archive\n"

        echo -e "  ${C_BOLD}${C_YELLOW}[ SYSTEM TOOLS ]${C_RESET}"
        echo -e "   ${C_CYAN}14)${C_RESET} System Doctor & Integrity Audit"
        echo -e "   ${C_CYAN}15)${C_RESET} Fix Permissions & Clear Caches"
        echo -e "   ${C_RED}0)${C_RESET}  Exit\n"

        read -rp " Enter your choice [0-15]: " menu_choice

        case "$menu_choice" in
            1) install_theme ;;
            2) rebuild_theme; press_enter ;;
            3) repair_theme ;;
            4) remove_theme ;;
            5) install_pterodactyl ;;
            6) rebuild_pterodactyl; press_enter ;;
            7) repair_pterodactyl ;;
            8) remove_pterodactyl ;;
            9) backup_theme_only; press_enter ;;
            10) backup_panel_files_only; press_enter ;;
            11) backup_database; press_enter ;;
            12) backup_full_system; press_enter ;;
            13) restore_backup_menu ;;
            14) system_doctor ;;
            15) fix_permissions; clear_panel_caches; press_enter ;;
            0)
                echo -e "\n ${C_GREEN}Goodbye!${C_RESET}\n"
                exit 0
                ;;
            *)
                print_error "Invalid option selected."
                sleep 1
                ;;
        esac
    done
}

# Execute main menu
main_menu "$@"
