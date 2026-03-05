FROM php:8-alpine
RUN apk add --no-cache docker-cli
WORKDIR /app
EXPOSE 8080
CMD ["php", "-S", "0.0.0.0:8080", "router.php"]
