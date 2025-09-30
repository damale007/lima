<?php 
    require_once '../app/includes/app.php';
    require '../app/clases/Migrations.php';

    use Modelo\Migrations;

    $mig = new Migrations();
    $sql = $mig->create();

    echo "Proceso concluido.";


    function createNewTable($tabla, $atributos) {
        $lines = [];
        $sql = 'CREATE TABLE '.$tabla.'(';
        $sql .= 'id INT(11) AUTO_INCREMENT PRIMARY KEY';

        $total = count($atributos);
        $voy = 1;

        if ($total > 0)
            $sql.=',';

        foreach ($atributos as $key => $values) {
            $sql.=$key;
            $fks = [];

            $valores = count($values);
            $voyVal = 1;
            foreach($values as $val) {
                $labels = explode(":", $val);
                if (count($labels) > 1) {
                    $label = strtolower($labels[0]);
                    $v = $labels[1];
                } else {
                    $label = strtolower($val);
                    $v= "";                
                }

                $code = "";
                switch ($label) {
                    case 'string':
                        if ($v != '')
                            $code = 'VARCHAR('.$v.')';
                        else
                            $code = 'TINYTEXT';
                        break;
                    case 'integer':
                        $code = 'INT(11)';
                        if ($v == 'small')
                            $code = 'SMALLINT';
                        if ($v == 'big')
                            $code = "BIGINT";
                        break;
                    case 'float':
                        $code = 'FLOAT';
                        if ($v == 'big')
                            $code = 'DOUBLE';
                        break;
                    case 'text':
                        $code = 'TEXT';
                        break;
                    case 'date':
                        $code = 'DATE';
                        break;
                    case 'datetime':
                        $code = 'DATETIME';
                        break;
                    case 'notnull':
                        $code = ' NOT NULL';
                        break;
                    case 'unique':
                        $code = ' UNIQUE';
                        break;
                    case 'fk':
                        $fks[] = [$key, $v];
                        break;
                }
                $sql.= " ".$code;
            }
            if ($voy < $total)
                $sql.=",";

            $voy++;
        }

        $sql.=");";

        $lines[] = $sql;
        $sql = "";

        $campos = [];
        $voy = 0;
        foreach ($fks as $line) {
            $nombre = $line[0];
            for ($i=0; $i<$voy; $i++)
                if ($nombre == $fks[$i][0])
                    $nombre .= getToken();

            $campos[] = $nombre;

            $sql = ' ALTER TABLE '.$tabla.' ADD CONSTRAINT '.$campos[$voy].'_FK FOREIGN KEY ('.$line[0].') REFERENCES '.$line[1].' (id);';
            $lines[] = $sql;
            $voy++;
        }

        return $lines;
    }