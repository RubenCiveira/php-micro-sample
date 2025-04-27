PHP_FILES := src/ tests/ # Ajusta a tus carpetas

.PHONY: lint test

# Ejecuta los tests unitarios
test:
	vendor/bin/phpunit

# Ejecuta análisis de estilo de código (linting)
lint:
	vendor/bin/psalm --no-cache

# Formatea automáticamente el código
#format:
#	vendor/bin/php-cs-fixer fix $(PHP_FILES)

