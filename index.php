<?php
// Se sono settate le due POST
if (isset($_POST['code_departure']) && isset($_POST['code_arrival'])) {
  // Memorizzo le due POST
  $codeDeparture = $_POST['code_departure'];
  $codeArrival = $_POST['code_arrival'];

  // # Apro una connessione con il database
  $servername = "localhost";
  $username = "root";
  $password = "root";
  $dbname = "exam";

  // Creo una nuova instanza di connessione
  $conn = new mysqli($servername, $username, $password, $dbname);

  // Faccio un check di connessione con il db
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }

  // Scrivo la query che mi restituisce un volo diretto, dato il codice di un aeroporto di partenza e di arrivo, ordino per prezzo e prendo un solo risultato
  $sql = "SELECT departure.name AS start_airport, arrival.name AS end_airport, price
          FROM `flights`
          JOIN `airports` AS departure ON departure.code = `flights`.`code_departure`
          JOIN `airports` AS arrival ON arrival.code = `flights`.`code_arrival`
          WHERE code_departure = $codeDeparture AND code_arrival = $codeArrival 
          ORDER BY price 
          LIMIT 1";
  // Mando la query       
  $result = $conn->query($sql);

  // # Se la query restituisce un risultato
  if ($result->num_rows > 0) {
    // Setto un booleano 'volo diretto' a TRUE
    $directFlight = true;
    // Resetto per sicurezza 'volo con scalo' a FALSE
    $flightWithStopover = false;
  } else {
    // # Altrimenti scrivo una query che mi restituisce un volo se presente con scalo. 
    $sql = "SELECT  departure.name AS start_airport, 
                    stopover.name AS intermediate_airport, 
                    arrival.name AS end_airport, 
                    first_flight.price AS first_flight_price, 
                    second_flight.price AS second_flight_price, 
                    (second_flight.price + first_flight.price) AS total_price
            FROM `flights`  AS first_flight
            JOIN `flights` AS second_flight ON second_flight.`code_departure` = first_flight.`code_arrival`
            JOIN `airports` AS departure ON departure.code = first_flight.`code_departure`
            JOIN `airports` AS arrival ON arrival.code = second_flight.`code_arrival`
            JOIN `airports` AS stopover ON stopover.code = first_flight.`code_arrival`
            WHERE first_flight.`code_departure` = $codeDeparture AND second_flight.`code_arrival` = $codeArrival
            ORDER BY (second_flight.price + first_flight.price) 
            LIMIT 1";

    // # Mando la nuova query
    $result = $conn->query($sql);
    // # Se la nuova query restituisce un risultato
    if ($result->num_rows > 0) {
      // Setto un booleano 'volo con scalo' a TRUE
      $flightWithStopover = true;
      // Resetto per sicurezza 'volo diretto' a FALSE
      $directFlight = false;
    }
  }
  // Chiudo la connessione con il DB
  $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
</head>

<body>
  <div class="container py-5">
    <!-- Form che richiama la stessa pagina -->
    <form action="" method="post" class="w-50 m-auto mb-5">
      <div class="main-box d-flex mb-3">
        <div class="select-box mx-2">
          <label for="code_departure">Aeroporto di Partenza</label>
          <select class="form-select" name="code_departure">
            <option selected value="001">Aeroporto di Napoli</option>
            <option value="002">Aeroporto di Milano Malpensa</option>
            <option value="003">Aeroporto di Roma</option>
          </select>
        </div>

        <div class="select-box mx-2">
          <label for="code_arrival">Aeroporto di Arrivo</label>
          <select class="form-select" name="code_arrival">
            <option value="001">Aeroporto di Napoli</option>
            <option selected value="002">Aeroporto di Milano Malpensa</option>
            <option value="003">Aeroporto di Roma</option>
          </select>
        </div>
      </div>

      <div class="mx-2">
        <button type="submit" class="btn btn-primary">Invia</button>
      </div>
    </form>

    <?php
    // # Se esiste un volo diretto
    if (isset($directFlight) && $directFlight) { ?>
      <h1>Volo diretto</h1>
      <table class="table">
        <tr>
          <th>Partenza</th>
          <th>Arrivo</th>
          <th>Prezzo biglietto</th>
        </tr>
        <?php
        // # Per ogni risultato creo una nuova riga per la tabella
        while ($row = $result->fetch_assoc()) {
          echo "<tr>
                <td>" . ucfirst($row["start_airport"]) . "</td>
                <td>" . ucfirst($row["end_airport"]) . "</td>
                <td>" . $row["price"] . "€</td>
              </tr>";
        } ?>
      </table>
    <?php
    } else if (isset($flightWithStopover) && $flightWithStopover) { ?>
      <!-- Altrimenti Se ci sono voli con scalo -->
      <h1>Volo con Scalo</h1>
      <table class="table">
        <tr>
          <th>Aeroporto di Partenza</th>
          <th>Aeroporto di Scalo</th>
          <th>Aeroporto di Arrivo</th>
          <th>Prezzo prima tappa</th>
          <th>Prezzo seconda tappa</th>
          <th>Prezzo totale</th>
        </tr>
        <?php
        // # Per ogni risultato creo una nuova riga per la tabella
        while ($row = $result->fetch_assoc()) {
          echo "<tr>
                <td>" . ucfirst($row["start_airport"]) . "</td>
                <td>" . ucfirst($row["intermediate_airport"]) . "</td>
                <td>" . ucfirst($row["end_airport"]) . "</td>
                <td>" . $row["first_flight_price"] . "€</td>
                <td>" . $row["second_flight_price"] . "€</td>
                <td>" . round($row["total_price"], 2) . "€</td>
              </tr>";
        } ?>
      </table>
    <?php
    } else { // # Altrimenti significa che non ci sono voli diretti, ne con scalo.
      echo "<h2>Non ho trovato risultati</h2>";
    }  ?>
  </div>
</body>

</html>
