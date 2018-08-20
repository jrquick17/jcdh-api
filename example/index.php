<!DOCTYPE html>
<html lang="en">
<head>
    
    <meta charset="UTF-8">
    <title>jcdh-api</title>

    <script src="https://code.jquery.com/jquery-3.3.1.min.js"
            integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
            crossorigin="anonymous"></script>

    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.bundle.min.js"></script>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" rel="stylesheet"/>
    
    <?php
        $output = array_key_exists('output', $_GET) ? $_GET['output'] : '';
        $types = array_key_exists('types', $_GET) ? $_GET['types'] : '';
    ?>
</head>
<body>
    <nav class="navbar navbar-light bg-light justify-content-between">
        <a class="navbar-brand"
           href="https://github.com/jrquick17/jcdh-api">
            <img src="data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4KPHN2ZyB3aWR0aD0iMTc5MiIg%0D%0AaGVpZ2h0PSIxNzkyIiB2aWV3Qm94PSIwIDAgMTc5MiAxNzkyIiB4bWxucz0iaHR0cDovL3d3dy53%0D%0AMy5vcmcvMjAwMC9zdmciPjxwYXRoIGQ9Ik04OTYgMTI4cTIwOSAwIDM4NS41IDEwM3QyNzkuNSAy%0D%0ANzkuNSAxMDMgMzg1LjVxMCAyNTEtMTQ2LjUgNDUxLjV0LTM3OC41IDI3Ny41cS0yNyA1LTQwLTd0%0D%0ALTEzLTMwcTAtMyAuNS03Ni41dC41LTEzNC41cTAtOTctNTItMTQyIDU3LTYgMTAyLjUtMTh0OTQt%0D%0AMzkgODEtNjYuNSA1My0xMDUgMjAuNS0xNTAuNXEwLTExOS03OS0yMDYgMzctOTEtOC0yMDQtMjgt%0D%0AOS04MSAxMXQtOTIgNDRsLTM4IDI0cS05My0yNi0xOTItMjZ0LTE5MiAyNnEtMTYtMTEtNDIuNS0y%0D%0AN3QtODMuNS0zOC41LTg1LTEzLjVxLTQ1IDExMy04IDIwNC03OSA4Ny03OSAyMDYgMCA4NSAyMC41%0D%0AIDE1MHQ1Mi41IDEwNSA4MC41IDY3IDk0IDM5IDEwMi41IDE4cS0zOSAzNi00OSAxMDMtMjEgMTAt%0D%0ANDUgMTV0LTU3IDUtNjUuNS0yMS41LTU1LjUtNjIuNXEtMTktMzItNDguNS01MnQtNDkuNS0yNGwt%0D%0AMjAtM3EtMjEgMC0yOSA0LjV0LTUgMTEuNSA5IDE0IDEzIDEybDcgNXEyMiAxMCA0My41IDM4dDMx%0D%0ALjUgNTFsMTAgMjNxMTMgMzggNDQgNjEuNXQ2NyAzMCA2OS41IDcgNTUuNS0zLjVsMjMtNHEwIDM4%0D%0AIC41IDg4LjV0LjUgNTQuNXEwIDE4LTEzIDMwdC00MCA3cS0yMzItNzctMzc4LjUtMjc3LjV0LTE0%0D%0ANi41LTQ1MS41cTAtMjA5IDEwMy0zODUuNXQyNzkuNS0yNzkuNSAzODUuNS0xMDN6bS00NzcgMTEw%0D%0AM3EzLTctNy0xMi0xMC0zLTEzIDItMyA3IDcgMTIgOSA2IDEzLTJ6bTMxIDM0cTctNS0yLTE2LTEw%0D%0ALTktMTYtMy03IDUgMiAxNiAxMCAxMCAxNiAzem0zMCA0NXE5LTcgMC0xOS04LTEzLTE3LTYtOSA1%0D%0AIDAgMTh0MTcgN3ptNDIgNDJxOC04LTQtMTktMTItMTItMjAtMy05IDggNCAxOSAxMiAxMiAyMCAz%0D%0Aem01NyAyNXEzLTExLTEzLTE2LTE1LTQtMTkgN3QxMyAxNXExNSA2IDE5LTZ6bTYzIDVxMC0xMy0x%0D%0ANy0xMS0xNiAwLTE2IDExIDAgMTMgMTcgMTEgMTYgMCAxNi0xMXptNTgtMTBxLTItMTEtMTgtOS0x%0D%0ANiAzLTE0IDE1dDE4IDggMTQtMTR6Ii8+PC9zdmc+" width="30" height="30" alt="">
            jcdh-api
        </a>

        <form class="form-inline my-2 my-lg-0">
            <a class="navbar-brand"
               href="https://www.jrquick.com">
                <button class="btn btn-outline-success my-2 my-sm-0"
                        type="button">
                    FIND MORE STUFF
                </button>
            </a>
        </form>
    </nav>

    <div class="card">
        <div class="card-body row">
            <div class="form-group col-12">
                <label for="types">
                    Types(s)
                </label>

                <input type="text"
                       class="form-control"
                       id="types"
                       aria-describedby="types"
                       placeholder="food"
                       required
                       aria-required="true"
                       <?php echo($types ? 'value="'.$types.'"' : '') ?>/>

                <small id="typesHelp"
                       class="form-text text-muted">
                    Separate types by comma (communal, food, hotel, pool, and/or tanning)
                </small>
            </div>
            
            <div class="form-group col-12">
                <label for="outputs">
                    Output
                </label>

                <input type="text"
                       class="form-control"
                       id="outputs"
                       aria-describedby="outputs"
                       placeholder="JSON"
                       required
                       aria-required="true"
                       <?php echo($output ? 'value="'.$output.'"' : '') ?>/>

                <small id="outputsHelp"
                       class="form-text text-muted">
                    JSON or XML
                </small>
            </div>

            <button id="request-button"
                    type="button"
                    class="btn btn-block btn-primary">
                Request
            </button>
        </div>

        <div id="response"
             class="card-body">
        </div>
    </div>

    <script type="text/javascript">
        function displayResponse(response) {
            var json = JSON.parse(response);
            var prettyJson = JSON.stringify(json, null, '\t');

            $('#response').text(prettyJson);
        }

        function getGet() {
            var data = '';
            
            var output = $('#output').val();
            if (output && output.length) {
                data += '&output=' + output;
            }

            var types = $('#types').val();
            if (types && types.length) {
                data += '&types=' + types;
            }

            return data;
        }

        function request() {
            var data = getGet();

            $.get('api.php?' + data).then(
                function(response) {
                    displayResponse(response);
                }
            );
        }

        $('#request-button').click(request);
    </script>
</body>
</html>