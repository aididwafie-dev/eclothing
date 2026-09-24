<!doctype html>
<html lang="ms">
<head>
	<meta charset="utf-8">
	<title>KEW.PS-8 - {{ $orderReference ?? 'Order #'.$order->id }}</title>
	<style>
		* { box-sizing: border-box; }
		body {
			font-family: Arial, Helvetica, sans-serif;
			font-size: 12px;
			color: #111;
			margin: 0;
			padding: 16px;
			background: #f2f2f2;
		}
		.no-print { text-align: right; margin-bottom: 12px; }
		.button {
			display: inline-block;
			padding: 8px 16px;
			background: #0d6efd;
			color: #fff;
			border: none;
			border-radius: 4px;
			cursor: pointer;
			font-size: 13px;
			text-decoration: none;
		}
		.kewps8-sheet {
			background: #fff;
			max-width: 1050px;
			margin: 0 auto 24px auto;
			padding: 20px 24px;
			border: 1px solid #999;
		}
		.kewps8-topbar {
			width: 100%;
			border-collapse: collapse;
			font-size: 11px;
			margin-bottom: 8px;
		}
		.kewps8-topbar td { border: none; padding: 0; vertical-align: top; }
		.kewps8-code { font-weight: bold; text-align: right; }
		.kewps8-no, .kewps8-page-note { text-align: right; }
		.kewps8-heading { text-align: center; margin-bottom: 12px; }
		.kewps8-title { font-weight: bold; font-size: 15px; letter-spacing: 0.5px; }
		.kewps8-subtitle { font-size: 12px; }
		.kewps8-order-info {
			width: 100%;
			font-size: 11px;
			margin-bottom: 10px;
		}
		.kewps8-order-info td { padding: 1px 0; }
		table.kewps8-table {
			width: 100%;
			border-collapse: collapse;
			table-layout: fixed;
		}
		table.kewps8-table th, table.kewps8-table td {
			border: 1px solid #333;
			padding: 4px 6px;
			vertical-align: middle;
			font-size: 11px;
			height: 26px;
			line-height: 26px;
			overflow: hidden;
		}
		table.kewps8-table thead th { text-align: center; background: #eee; line-height: 1.3; height: auto; }
		/* The text columns are narrower now that the three blocks share the
		   width evenly, and the longest stock descriptions and remarks ("NOT
		   APPLICABLE") no longer fit on one line. Cells clip rather than wrap
		   by default, and the PDF renderer instead steals width from a
		   neighbouring column to fit them, which tips the blocks out of
		   balance. Two 13px lines come to the same 26px as every other row, so
		   wrapping here keeps both the grid and the thirds even. */
		table.kewps8-table td.col-wrap {
			white-space: normal;
			line-height: 13px;
			/* "(Airmen)/Kain(Airwomen)" carries no space to break at, and a
			   column is never made narrower than its longest unbreakable word,
			   so without this one such entry widens its column and pulls the
			   blocks off a third each. */
			word-wrap: break-word;
			word-break: break-word;
		}
		.text-center { text-align: center; }
		.sign-cell { vertical-align: top; height: auto; line-height: normal; overflow: visible; }
		.sign-title { font-weight: bold; margin-bottom: 24px; }
		.sign-stroke { margin-bottom: 2px; }
		.sign-note { font-style: italic; margin-bottom: 6px; }
		.sign-row { margin-bottom: 2px; }
		.sign-row span { display: inline-block; width: 60px; }
		@media print {
			body { background: #fff; padding: 0; }
			.no-print { display: none; }
			.kewps8-sheet { border: none; margin: 0 0 12px 0; page-break-after: always; }
		}
	</style>
	@if($forPdf ?? false)
		{{-- Server-side PDF (dompdf) doesn't honour @media print, so apply the
		     same print appearance unconditionally when rendering to a file. --}}
		<style>
			body { background: #fff; padding: 0; }
			.no-print { display: none; }
			.kewps8-sheet { border: none; margin: 0 0 12px 0; page-break-after: always; }
			/* Under table-layout:fixed dompdf drops the column widths and
			   spaces all nine evenly, which is what pushed the three blocks to
			   44/33/22. Its automatic layout does read them. */
			table.kewps8-table { table-layout: auto; }
		</style>
	@endif
</head>
<body>

<div class="no-print">
	<button type="button" class="button" onclick="window.print()">Print</button>
</div>

@foreach($reportForms as $formIndex => $reportRows)
	<section class="kewps8-sheet">
		<table class="kewps8-topbar">
			<tr>
				<td style="text-align: left;">Pekeliling Perbendaharaan Malaysia</td>
				<td style="text-align: right;">
					<div class="kewps8-code">KEW.PS-8</div>
					<div class="kewps8-no">No. Rujukan : {{ $orderReference ?? $order->id }}</div>
					@if(count($reportForms) > 1)
						<div class="kewps8-page-note">Borang {{ $formIndex + 1 }} / {{ count($reportForms) }}</div>
					@endif
				</td>
			</tr>
		</table>

		<div class="kewps8-heading">
			<div class="kewps8-title">BORANG PERMOHONAN STOK</div>
			<div class="kewps8-subtitle">(INDIVIDU KEPADA STOR)</div>
		</div>

		<table class="kewps8-table">
			{{-- The three blocks carry equal weight on the form, so each gets a
			     third of the width and the signature boxes below them come out
			     the same size. Pegawai Pelulus and Perakuan Penerimaan split
			     their third evenly; Permohonan does not, because Bil. holds a
			     row number and Perihal Stok holds the longest text on the
			     form. Each block still totals a third. --}}
			<colgroup>
				<col style="width: 4%">
				<col style="width: 14.34%">
				<col style="width: 6%">
				<col style="width: 9%">
				<col style="width: 11.11%">
				<col style="width: 11.11%">
				<col style="width: 11.11%">
				<col style="width: 16.67%">
				<col style="width: 16.66%">
			</colgroup>
			<thead>
			<tr>
				<th colspan="4">Permohonan</th>
				<th colspan="3">Pegawai Pelulus</th>
				<th colspan="2">Perakuan Penerimaan</th>
			</tr>
			{{-- The widths are repeated here because the PDF renderer reads
			     them from the cells, not from the <colgroup>: without them it
			     sizes the columns by their contents, so the three blocks come
			     out differently on every order. Browsers use the colgroup, so
			     the two must be kept in step. --}}
			<tr>
				<th style="width: 4%">Bil.</th>
				<th style="width: 14.34%">Perihal Stok</th>
				<th style="width: 6%">Kuantiti<br>Dimohon</th>
				<th style="width: 9%">Catatan</th>
				<th style="width: 11.11%">Baki Sedia<br>Ada</th>
				<th style="width: 11.11%">Kuantiti<br>Diluluskan</th>
				<th style="width: 11.11%">Catatan</th>
				<th style="width: 16.67%">Kuantiti<br>Diterima</th>
				<th style="width: 16.66%">Catatan</th>
			</tr>
			</thead>
			<tbody>
			@foreach($reportRows as $row)
				<tr>
					<td class="text-center">{{ $row['bil'] }}</td>
					<td class="col-wrap">{{ $row['perihal'] }}</td>
					<td class="text-center">{{ $row['dimohon'] }}</td>
					<td class="col-wrap">{{ $row['catatan'] }}</td>
					<td class="text-center">{{ $row['baki'] ?? '' }}</td>
					<td class="text-center">{{ $row['diluluskan'] ?? '' }}</td>
					<td class="col-wrap">{{ $row['catatan_pelulus'] ?? '' }}</td>
					<td class="text-center">{{ $row['diterima'] ?? '' }}</td>
					<td class="col-wrap">{{ $row['catatan_terima'] ?? '' }}</td>
				</tr>
			@endforeach
			</tbody>
			<tfoot>
			<tr>
				<td colspan="4" class="sign-cell">
					<div class="sign-title">Pemohon:</div>
					<div class="sign-stroke">.............................................</div>
					<div class="sign-note">(Tandatangan)</div>
					<div class="sign-row"><span>Nama</span>: {{ $applicantName }}</div>
					<div class="sign-row"><span>Jawatan</span>: {{ $applicantPosition ?? '' }}</div>
					<div class="sign-row"><span>Tarikh</span>: {{ $printedAt ?? '' }}</div>
				</td>
				<td colspan="3" class="sign-cell">
					<div class="sign-title">Pegawai Pelulus:</div>
					<div class="sign-stroke">.............................................</div>
					<div class="sign-note">(Tandatangan)</div>
					<div class="sign-row"><span>Nama</span>: {{ $approver['name'] ?? '' }}</div>
					<div class="sign-row"><span>Jawatan</span>: {{ $approver['position'] ?? '' }}</div>
					<div class="sign-row"><span>Tarikh</span>: {{ $approver['approved_at'] ?? '' }}</div>
				</td>
				<td colspan="2" class="sign-cell">
					<div class="sign-title">Pemohon/ Wakil:</div>
					<div class="sign-stroke">.............................................</div>
					<div class="sign-note">(Tandatangan)</div>
					{{-- Blank until the order is Completed; see kewPs8Receipt(). --}}
					<div class="sign-row"><span>Nama</span>: {{ $receipt['name'] ?? '' }}</div>
					<div class="sign-row"><span>Jawatan</span>: {{ $receipt['position'] ?? '' }}</div>
					<div class="sign-row"><span>Tarikh</span>: {{ $receipt['received_at'] ?? '' }}</div>
				</td>
			</tr>
			</tfoot>
		</table>
	</section>
@endforeach

</body>
</html>
