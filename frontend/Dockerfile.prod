# FRONTEND
#BUILD Stage
# Starte node.js Umgebung um react zu bauen und nennt diese stage build (steht für multi stage build =  jedes FROM startet neue stage, kann von einer dateien in die andere kopieren = kleiner images); bentöige für ich prod udn entwicklung - bei prod sind weniger dependencies dabei
FROM node:18 AS build
# arbeitsverzeichnis im container, die befehle beziehen sich auf app
WORKDIR /app
# kopiert die package dateien
COPY package*.json  ./
# installiert node dependencies
RUN npm install 
# kopiert den restlichen react code
COPY src ./src
COPY public ./public
# erstellt producion build -> html, css, js in dist/ (bei vite)
RUN npm run build


# NGINX Stage
# Erstellt einen nginx container
FROM nginx:alpine
# fertiges react wird aus der build-stage in das nginx verzeichnix kopiert
COPY --from=build /app/build /usr/share/nginx/html
# kopiert meine nginx konfiguration ins container verzeichnis
COPY nginx.conf /etc/nginx/conf.d/default.conf
# docker soll container port 80 benutzen
EXPOSE 80



