# MenetZero - Carbon Emission Management System

A Laravel-based web application for managing and tracking carbon emissions for companies.

## Production Deployment

This application is configured for production deployment at `app.menetzero.com`.

### Server Requirements

- PHP 8.2 or higher
- MySQL 5.7 or higher
- Composer
- Web server (Apache/Nginx)

### Database Configuration

- Database: `silverwebbuzz_in_menetzero`
- Username: `silverwebbuzz_in_menetzero`
- Password: `MEnet$zero@123`

### Deployment Steps

1. Upload all files to `/home/silverwebbuzz_in/public_html/menetzero/app`
2. Run the deployment script: `./deploy.sh`
3. Ensure proper file permissions are set
4. Configure your web server to point to the `public` directory

### Environment Configuration

The application is pre-configured with production settings:
- Debug mode: Disabled
- Environment: Production
- Database: MySQL
- URL: https://app.menetzero.com

### Features

- Company carbon emission tracking
- Emission factor management
- Carbon calculation services
- User authentication and permissions
- Report generation
- Excel export functionality

### Security

- Application key is pre-generated
- Debug mode is disabled
- Production-optimized configuration
- Secure database credentials

For support or questions, contact the development team.