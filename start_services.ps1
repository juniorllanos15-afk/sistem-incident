# start_services.ps1
# This script starts all microservices on their respective ports using individual terminal windows.

Write-Host "Starting Incident Management Microservices..." -ForegroundColor Cyan

# Service 1: API Gateway (Port 8000)
Write-Host "Launching API Gateway on port 8000..."
Start-Process powershell -ArgumentList "-NoExit", "-Command", "Write-Host 'API Gateway - Port 8000' -ForegroundColor Yellow; php -S localhost:8000 -t api-gateway/"

# Service 2: Category Service (Port 8001)
Write-Host "Launching Category Service on port 8001..."
Start-Process powershell -ArgumentList "-NoExit", "-Command", "Write-Host 'Category Service - Port 8001' -ForegroundColor Green; php -S localhost:8001 -t category-service/"

# Service 3: Role Service (Port 8002)
Write-Host "Launching Role Service on port 8002..."
Start-Process powershell -ArgumentList "-NoExit", "-Command", "Write-Host 'Role Service - Port 8002' -ForegroundColor Cyan; php -S localhost:8002 -t rol-service/"

# Service 4: User Service (Port 8003)
Write-Host "Launching User Service on port 8003..."
Start-Process powershell -ArgumentList "-NoExit", "-Command", "Write-Host 'User Service - Port 8003' -ForegroundColor Magenta; php -S localhost:8003 -t user-service/"

# Service 5: Incident Service (Port 8004)
Write-Host "Launching Incident Service on port 8004..."
Start-Process powershell -ArgumentList "-NoExit", "-Command", "Write-Host 'Incident Service - Port 8004' -ForegroundColor Magenta; php -S localhost:8004 -t incident-service/"

# Service 6: Assignment Service (Port 8005)
Write-Host "Launching Assignment Service on port 8005..."
Start-Process powershell -ArgumentList "-NoExit", "-Command", "Write-Host 'Assignment Service - Port 8005' -ForegroundColor Blue; php -S localhost:8005 -t assignment-service/"

# Service 7: Solution Service (Port 8006)
Write-Host "Launching Solution Service on port 8006..."
Start-Process powershell -ArgumentList "-NoExit", "-Command", "Write-Host 'Solution Service - Port 8006' -ForegroundColor Yellow; php -S localhost:8006 -t solution-service/"

Write-Host "`nAll services have been launched in separate windows." -ForegroundColor White
Write-Host "You can now access the project." -ForegroundColor Cyan
