# Attendance and Leave Management System
This is a project I do for internship in a local car reseller but I hope this helpfull for someone out there.

## What you can do with this project
You can just run it locally to see whats inside or maybe use it and run it inside vps.

### Run the code locally
0. Makes sure you have installed docker since the project heavily use docker
1. Clone the repo
```bash
git clone https://github.com/arbipink/HRIS-Laravel.git --depth 1
```
2. Copy the .env file
```bash
cp .env.example .env
```
3. Uncomment these lines and change the password to your liking
```bash
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=hrms_db
DB_USERNAME=hrms
DB_PASSWORD=secret
DB_ROOT_PASSWORD=verysecret
```
4. Make the script executable
```bash
chmod +x setup.sh
```
5. Run the script
```bash
./setup.sh
```
6. The commands above only target unix based OS (Linux, MacOS, BSD, etc.),
   if you are on windows your best bet is wsl or third party tools (like Cygwin and git bash but i dont know i never tried it)
7. Now open [your localhost](https://www.localhost/)
8. If you want to manage the database, its in [port 8080](https://www.localhost:8080)
9. Now use the code to your liking 😁

## Run the code on VPS
Its kinda a hussle but I'll write the guide for it in the future ✌️
