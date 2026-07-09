<div class="containerMain">
	<div class="content table_center">						
		<p style="margin-bottom: 8px">Hello User!</p>			
		<p style="margin-bottom: 8px; margin-top: 0">This is the list of Uniforms Ordered by you :</p>
		<table cellpadding="0" cellspacing="0" style="border-collapse: collapse;">
			<tr style="background: #f9f9f9;">
				<th style="width: 125px; text-align: center;border: 1px solid #d2d0d0; padding: 8px;">Uniform Type</th>
				<th style="width: 150px; text-align: center;border: 1px solid #d2d0d0; padding: 8px;">Clothe name</th>
				<th style="width: 150px; text-align: center;border: 1px solid #d2d0d0; padding: 8px;">Size ordered</th>
			</tr>
			@foreach($data as $array)
				
				@foreach($array['orderDetails'] as $order_key => $clothsDetails)
					<tr>
						@if($order_key == 0)
							<td rowspan="{{ $array['count'] }}" style="width: 125px; text-align: center;border: 1px solid #d2d0d0; padding: 8px;background: #f9f9f9;"><label>{{$array['orderedUniform']->uniform_type}}</label></td>
						@endif
						<td style="width: 150px; text-align: center;border: 1px solid #d2d0d0; padding: 8px; border-top: 0;border-left: 0; white-space: normal; word-wrap: break-word;">{{$clothsDetails->clothes}}</td>
						<td style="width: 150px; text-align: center;border: 1px solid #d2d0d0; padding: 8px; border-top: 0;border-left: 0">{{$clothsDetails->size}}</td>
					</tr>
				@endforeach
			@endforeach
		</table>
	</div>
</div>