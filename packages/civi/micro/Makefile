PHP_FILES := src/ tests/ # Ajusta a tus carpetas

.PHONY: lint test format

# Ejecuta los tests unitarios
test:
	vendor/bin/phpunit

# Ejecuta análisis de estilo de código (linting)
lint:
#	vendor/bin/phpstan analyse $(PHP_FILES)
	vendor/bin/phpmd $(PHP_FILES) text phpmd.xml
	vendor/bin/phpcs $(PHP_FILES)

# Formatea automáticamente el código
format:
	vendor/bin/php-cs-fixer fix $(PHP_FILES)

