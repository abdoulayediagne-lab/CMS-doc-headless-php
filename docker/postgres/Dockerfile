FROM postgres:18

# Copier les scripts d'initialisation
COPY sql/init/00-init-db.sh /docker-entrypoint-initdb.d/
COPY sql/migrations/ /docker-entrypoint-initdb.d/migrations/
COPY sql/seeders/ /docker-entrypoint-initdb.d/seeders/

# Donner les droits d'exécution
RUN chmod +x /docker-entrypoint-initdb.d/00-init-db.sh
