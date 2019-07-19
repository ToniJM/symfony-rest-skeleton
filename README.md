Persona
=======
Prerequisitos
-------------
These requirements are those that were used when developing. It may work with previous versions
- Openssl
- PHP 7.3.7
- Symfony 4.3+

Instalation
-----------

#### Clone this repo

    git clone "https://github.com/ToniJM/persona.git"

#### Install dependencies

    composer install

#### Generate the SSH keys

``` bash
$ mkdir -p config/jwt
$ openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096
$ openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout
```

Configuration
-------------

#### DB

Configure the DB URL in `.env` or in your `.env.local`:

``` bash
DATABASE_DRIVER=pgsql
# DATABASE_URL=pgsql://postgres:postgres@127.0.0.1:5432/persona
```

#### SSH Keys

Configure the SSH keys path in `.env` or in your `.env.local`:

```bash
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_TOKEN_TTL=3600
# JWT_PASSPHRASE=******
```
