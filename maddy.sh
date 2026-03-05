echo "Rebuilding Maddy mail server..."
docker compose -f maddy/docker-compose.yml down
docker compose -f maddy/docker-compose.yml up -d --build

echo "Maddy mail server is starting up. Checking the logs:"
docker logs -f maddy