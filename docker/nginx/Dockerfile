FROM nginx:alpine

# Instalar dependências necessárias
RUN apk add --no-cache \
    openssl \
    curl

# Criar diretório para certificados SSL
RUN mkdir -p /etc/nginx/ssl

# Gerar certificado SSL auto-assinado
RUN openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/nginx/ssl/nginx.key \
    -out /etc/nginx/ssl/nginx.crt \
    -subj "/C=BR/ST=SP/L=SP/O=Dev/CN=localhost"

# Copiar configurações do Nginx
COPY app.conf /etc/nginx/conf.d/default.conf

# Expor portas
EXPOSE 80 443 