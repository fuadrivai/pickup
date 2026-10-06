<!DOCTYPE html>
<html>
  <head>
    <title>IMPORT CSV FILE TO MYSQL #2</title>
  </head>
  <body>
  <?php
    //-- konfigurasi koneksi ke server database
    $dbhost='localhost';
    $dbuser='root';
    $dbpass='';
    $dbname='pickup';
    //-- membuat koneksi ke database server
    $db=new mysqli($dbhost,$dbuser,$dbpass,$dbname);
    if (isset($_POST['submit'])) {//Script akan berjalan jika di tekan tombol submit..
      //Script upload file csv..
      if (is_uploaded_file($_FILES['filename']['tmp_name'])) {
        echo "<h1>" . "File ". $_FILES['filename']['name'] ." Berhasil di Upload" . "</h1>";
        echo "<h2>Menampilkan Hasil Upload:</h2>";
        readfile($_FILES['filename']['tmp_name']);
      }
      //Import uploaded file ke Database, Letakan dibawah sini..
      $handle = fopen($_FILES['filename']['tmp_name'], "r"); //Membuka file dan membacanya
      $sql="INSERT INTO student (rfidid,student_name,grade) VALUES";
      $values=array();
      while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $values[]="('{$data[0]}','{$data[1]}','{$data[2]}')";
      }
      $db->query($sql.implode(',',$values));
      fclose($handle); //Menutup CSV file
      echo "<br><strong>Import data selesai.</strong>";
    }else { ?>
      <b>Silahkan masukan file csv yang ingin diupload</b><br /> 
      <form enctype='multipart/form-data' action='' method='post'>
        <input type='file' name='filename' size='100' /><br />
        <input type='submit' name='submit' value='Upload' />
      </form>
    <?php 
    }
    $db->close(); //Menutup koneksi ke database
    ?>
  </body>
</html>