# japfood-server

Welcome to the `japfood-server` repository, the server part of the Japfood online catalog. This repository complements the frontend application, which is located at [GamesFire/japfood](https://github.com/GamesFire/japfood.git).

## Overview

`japfood-server` is the server-side component of the Japfood online catalog. It is written in PHP and is responsible for handling all server-side operations, including data manipulation, interactions with the MySQL database, and responding to requests from the frontend application.

## Technologies Used

- **PHP:** The server-side logic is implemented in PHP.
- **MySQL Database:** All data is stored in a MySQL database managed through phpMyAdmin.

## Database Setup

To set up the database, you can use the provided `japfood.sql` file, which can be downloaded [here](./database/japfood.sql). Import this file into your MySQL database using phpMyAdmin or a similar tool.

## Server Operations

The `japfood-server` performs the following operations:

1. **Data Manipulation:** Handles data received from the frontend application, including adding, updating, and deleting food card information in the database.

2. **Retrieve Row Count:** Provides functionality to retrieve the total number of rows in a specified table of the database.

3. **Search by Name:** Allows searching for food cards by name in the database.

4. **Admin Manipulations:** Implements all necessary admin manipulations, such as adding, deleting, and updating food cards in the database.

## Frontend Communication

The server seamlessly communicates with the frontend part of the Japfood application. It processes incoming requests from the frontend, performs the necessary operations on the server side, and sends back responses to the client side.

## Usage

1. Clone the repository:

   ```bash
   git clone https://github.com/GamesFire/japfood-server.git
   cd japfood-server
   ```

2. Configure your server environment (e.g., Apache) to serve PHP files.

3. Import the japfood.sql file into your MySQL database.

4. Update the database connection details in the PHP files as needed.

5. Start using the server in conjunction with the Japfood frontend application.

## Contributing

Contributions are welcome! If you have suggestions, found a bug, or want to contribute to the project, please open an issue or submit a pull request.
