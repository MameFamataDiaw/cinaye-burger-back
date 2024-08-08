<!DOCTYPE html>
<html>
<head>
    <title>Facture de Commande</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .invoice-box {
            max-width: 800px;
            margin: auto;
            padding: 30px;
            border: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
        }
        .invoice-box table {
            width: 100%;
            line-height: inherit;
            text-align: left;
        }
        .invoice-box table td {
            padding: 5px;
            vertical-align: top;
        }
        .invoice-box table tr td:nth-child(2) {
            text-align: right;
        }
        .invoice-box table tr.top table td {
            padding-bottom: 20px;
        }
        .invoice-box table tr.information table td {
            padding-bottom: 40px;
        }
        .invoice-box table tr.heading td {
            background: #eee;
            border-bottom: 1px solid #ddd;
            font-weight: bold;
        }
        .invoice-box table tr.details td {
            padding-bottom: 20px;
        }
        .invoice-box table tr.item td{
            border-bottom: 1px solid #eee;
        }
        .invoice-box table tr.item.last td {
            border-bottom: none;
        }
        .invoice-box table tr.total td:nth-child(2) {
            border-top: 2px solid #eee;
            font-weight: bold;
        }
    </style>
</head>
<body>
<h1>Facture de la commande #{{ $commande->id }}</h1>
{{--<p><strong>Date :</strong> {{ $commande->date->format('d-m-Y') }}</p>--}}
<p><strong>Nom du Client :</strong> {{ $commande->nom_client }} {{ $commande->prenom_client }}</p>
<p><strong>Téléphone :</strong> {{ $commande->telephone_client }}</p>
<p><strong>Email :</strong> {{ $commande->email_client }}</p>

<h2>Détails de la Commande</h2>
<div class="invoice-box">
    <h1>Facture de Commande</h1>
    <table>
        <tr class="heading">
            <td>Nom du Burger</td>
            <td>Quantité</td>
            <td>Prix Unitaire</td>
            <td>Montant</td>
        </tr>
        @foreach ($details as $detail)
            <tr class="item">
                <td>{{ $detail->burger->nom }}</td>
                <td>{{ $detail->quantite }}</td>
                <td>{{ $detail->prix }}€</td>
                <td>{{ $detail->montant }}€</td>
            </tr>
        @endforeach
        <tr class="total">
            <td></td>
            <td></td>
            <td>Total:</td>
            <td>{{ $commande->total }}€</td>
        </tr>
    </table>
</div>
</body>
</html>

