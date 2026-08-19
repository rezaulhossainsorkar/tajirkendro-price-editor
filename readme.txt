KTPE Development Setup
======================

1. Open the GitHub repository.

2. Open the project in GitHub Codespaces.

3. Open the Codespace in your local VS Code.

4. Open the VS Code terminal.

5. Start WordPress:

   docker compose up -d

6. Open WordPress in your browser:

   http://127.0.0.1:8080/

7. Open WordPress Admin:

   http://127.0.0.1:8080/wp-admin/

8. Develop the plugin normally in VS Code.

9. Save your code and refresh the browser to see changes.

10. When finished, stop the environment:

    docker compose down

Important:
----------

Do not use:

    docker compose down -v

because it will remove the WordPress and database volumes.