# Lsky Pro - AI Coding Agent Instructions

## Project Overview
Lsky Pro is a Laravel-based photo album cloud storage system with multi-strategy storage support, user management, and comprehensive image management capabilities. The project uses Laravel 9.x, TailwindCSS, and supports multiple cloud storage providers.

## Architecture & Key Patterns

### Core Structure
- **Laravel MVC Architecture**: Standard Laravel structure with Models, Controllers, Services, and Views
- **Multi-Strategy Storage System**: Abstracted storage strategies via `Strategy` model and `StrategyKey` enum
- **Role-Based Access Control**: Groups with configurable permissions and storage strategies
- **Service Layer**: Heavy business logic in `app/Services/` (ImageService, UpgradeService, UserService)

### Key Components

#### Storage Strategies (app/Enums/StrategyKey.php)
```php
const Local = 1;    // Local filesystem
const S3 = 2;       // AWS S3
const Oss = 3;      // Aliyun OSS
const Cos = 4;      // Tencent Cloud COS
const Kodo = 5;     // Qiniu Cloud
const Uss = 6;      // Upyun
const Sftp = 7;     // SFTP
const Ftp = 8;      // FTP
const WebDav = 9;   // WebDAV
const Minio = 10;   // Minio
```

#### Configuration System (app/Enums/ConfigKey.php)
- Uses enum constants for all system configuration keys
- Configs stored in `configs` table with caching layer
- `Utils::config()` method for accessing configuration with dot notation support

#### Image Processing Pipeline
1. **Upload**: Via `ImageService::upload()` - handles validation, storage strategy selection
2. **Processing**: Intervention Image for manipulation, watermarking
3. **Storage**: Flysystem abstraction for multiple storage backends
4. **Metadata**: MD5/SHA1 hashing, dimension tracking, permission management

### Database Schema Highlights

#### Images Table
- Foreign keys: `user_id`, `album_id`, `group_id`, `strategy_id`
- Unique `key` field for image identification
- Metadata: size, mimetype, extension, dimensions, hash values
- Permissions: `permission` (0=private, 1=public), `is_unhealthy` flag

#### Users Table  
- Capacity tracking: `capacity`, `use_capacity`
- Group membership: `group_id` with role-based permissions
- Admin flag: `is_adminer`

#### Strategies Table
- Configuration stored as JSON in `config` column
- Type field maps to `StrategyKey` constants
- Groups relationship via pivot table

## Development Workflows

### Installation & Setup
```bash
# Standard Laravel setup
composer install
npm install
npm run production  # Build assets

# Application installation
php artisan lsky:install  # Interactive installation command
```

### Asset Management
```bash
npm run dev      # Development build with hot reload
npm run watch    # Watch mode for assets
npm run prod     # Production build
```

### Testing
```bash
php artisan test  # Run all tests
```

**Note**: The project uses Laravel's testing framework with DatabaseMigrations trait for feature tests. Tests are minimal in the base repo - focus on adding tests for new functionality.

### Database Migrations
```bash
php artisan migrate  # Run migrations
php artisan migrate:fresh --seed  # Fresh install with seeders
```

### Key Commands
- `php artisan lsky:install` - Interactive installer with DB config
- `php artisan lsky:upgrade` - System upgrade handler
- `php artisan lsky:make-thumbnails` - Thumbnail generation utility

## Critical Patterns & Conventions

### 1. Configuration Access
**Pattern**: Always use `Utils::config()` instead of direct config() calls
```php
// ✅ Correct
$isEnabled = Utils::config(ConfigKey::IsEnableGallery);

// ❌ Avoid  
$config = config('app.name');  // Bypasses the custom config system
```

### 2. Storage Strategy Implementation
**Pattern**: Strategy classes follow the Flysystem adapter pattern. Each strategy type has specific configuration options.

```php
// Strategy configuration stored as JSON
$strategy->config = [
    'bucket' => 'my-bucket',
    'region' => 'us-east-1',
    'access_key' => '...',
    'secret_key' => '...'
];
```

### 3. Image Permission System
- `0` = Private (user-only access)
- `1` = Public (anyone can view)
- `2` = Album-specific (not implemented in base enum but referenced)

### 4. User Capacity Management
- Users have `capacity` (total allowed) and `use_capacity` (current usage)
- Both stored in KB
- Checked during upload: `if ($user->use_capacity + $size > $user->capacity)`

### 5. Search Query Syntax (admin image search)
Supports advanced search operators:
- `is:public`, `is:private`, `is:unhealthy`, `is:guest`, `is:adminer`
- `order:earliest`, `order:utmost`, `order:least`
- `name:`, `album:`, `group:`, `strategy:`, `email:`, `extension:`, `md5:`, `sha1:`, `ip:`

### 6. Middleware Patterns
- `CheckIsInstalled` - Ensures app is installed before use
- `CheckIsEnableApi` - API access toggle
- `CheckIsEnableGallery` - Gallery feature toggle
- `CheckIsEnableGuestUpload` - Guest upload toggle

### 7. Frontend Asset Dependencies
**Critical dependencies** (must be preserved):
- jQuery + jQuery File Upload (legacy upload system)
- Alpine.js (frontend interactivity)
- TailwindCSS (styling)
- Intervention/Image (server-side image processing)
- Various Flysystem adapters (storage backends)

### 8. API Versioning
- Current: `v1` in `routes/api.php`
- Authentication: Laravel Sanctum tokens
- Rate limiting: 3 requests/minute for token creation

## Key Files Reference

### Core Logic
- `app/Services/ImageService.php` - 650+ lines - All image operations
- `app/Services/UpgradeService.php` - System updates
- `app/Services/UserService.php` - User management
- `app/Utils.php` - Utility functions and config access

### Models
- `app/Models/User.php` - Extended with capacity, group relationships
- `app/Models/Image.php` - Central to all image operations
- `app/Models/Strategy.php` - Storage strategy configuration
- `app/Models/Group.php` - Role-based permissions

### Controllers
- `app/Http/Controllers/Admin/*` - Admin panel controllers
- `app/Http/Controllers/User/*` - User dashboard controllers  
- `app/Http/Controllers/Api/V1/*` - REST API endpoints
- `app/Http/Controllers/Common/*` - Public-facing controllers

### Configuration
- `config/app.php` - Standard Laravel config
- `config/filesystems.php` - Flysystem configuration
- `config/image.php` - Intervention Image settings

## Important Notes for AI Agents

### 1. Legacy Code Considerations
- Some jQuery-based upload code exists alongside modern Alpine.js
- Mix of old and new Laravel patterns (the project has evolved)
- Maintain consistency with existing patterns when possible

### 2. Security Patterns
- Always validate file uploads thoroughly (mimetype, size, extension)
- Check user capacity before storage operations
- Use permission checks for image access
- Sanitize all user input for search queries

### 3. Storage Abstraction
- All file operations go through Flysystem
- Strategy selection based on user/group configuration
- Never directly access local filesystem - use storage facade

### 4. Performance Considerations
- Images table can grow very large - ensure proper indexing
- Configuration is cached - use `Utils::config()` for performance
- Thumbnail generation is on-demand, consider caching strategy

### 5. Error Handling
- `UploadException` for upload-specific errors
- `Utils::e()` for logging exceptions with context
- Return structured responses via `Result` trait

### 6. Testing Approach
- Use `DatabaseMigrations` trait for feature tests
- Mock external services (S3, email, etc.)
- Focus on service layer and API endpoints
- Test file upload scenarios with temporary storage

## External Dependencies & Services

### Required PHP Extensions
- BCMath, Ctype, DOM, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML
- **Imagick** - Critical for image processing
- exec, shell_exec functions - Used for system operations

### Storage Providers
- Flysystem v3 adapters for all providers
- Vendor-specific packages: `overtrue/flysystem-*`, `league/flysystem-*`

### Content Moderation
- Aliyun Green API integration
- Tencent IMS integration  
- NSFW.js client-side detection (referenced)

### Email & Notifications
- Laravel Mail system with multiple mailer support
- Configurable via `ConfigKey::Mail`

## Common Development Tasks

### Adding a New Storage Strategy
1. Add constant to `app/Enums/StrategyKey.php`
2. Create strategy-specific enum in `app/Enums/Strategy/`
3. Add Flysystem adapter to `composer.json`
4. Update `ImageService::getFilesystem()` method
5. Add configuration UI in admin strategy views

### Adding New Image Processing Feature
1. Extend `ImageService` methods
2. Use Intervention Image library
3. Respect user/group permissions
4. Update API endpoints if needed
5. Add tests in `tests/Feature/`

### Modifying Upload Flow
1. `app/Http/Controllers/User/ImageController.php::upload()`
2. `app/Services/ImageService.php::upload()` - Core logic
3. Storage strategy selection in `ImageService`
4. Validation in upload request classes

## Troubleshooting

### Common Issues
- **Imagick not installed**: Required for all image operations
- **Storage permission errors**: Ensure proper permissions on storage directory
- **Missing Flysystem adapters**: Install specific provider packages
- **Cache issues**: Clear config cache with `php artisan config:clear`

### Debug Mode
- Set `APP_DEBUG=true` in `.env` for detailed errors
- Use Laravel Debugbar (dev dependency) for debugging
- Check `storage/logs/laravel.log` for application logs

---

**Last Updated**: Generated based on codebase analysis as of December 2025
**Project Version**: Laravel 9.x, PHP 8.0+
**License**: GPL 3.0