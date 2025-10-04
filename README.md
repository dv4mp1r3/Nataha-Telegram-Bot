# First steps
Fill environment variables 
```
cp .env.example .env && nano .env
```

# docker compose (dev)
```
docker compose -f docker-compose.84.yaml -f docker-compose.84.dev.yaml build --no-cache
```

# minikube

```
docker compose -f docker-compose.84.yaml build --no-cache
minikube image load nataha-php-telegram:1.0
minikube image load nataha-nginx:1.0
minikube image load nataha-node:1.0
kubectl create configmap --from-env-file=.env vars.env
kubectl apply -f nginx-service.yaml,nginx-deployment.yaml, \ 
node-deployment.yaml,node-service.yaml, \ 
php-telegram-deployment.yaml,php-telegram-service.yaml, \ 
php-twitch-deployment.yaml
```