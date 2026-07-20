@include('static-layout/header')
@include('static-layout/admin_sidebar')

					</br>
					<div class="title"><i class="fa fa-pencil-square" aria-hidden="true"></i> Edit Tred</div>
					<hr>
						<div class="containerMain">
							<div class="content">
									<B>Edit tred details - {{ $tred->value }}</B>
								<hr>
								<form autocomplete="off" method="post" action="{{ url('/save-edited-tred') }}" name="save-unit" id="save-unit">
					
									<input type = "hidden" name = "_token" value = "<?php echo csrf_token(); ?>">
									<input type = "hidden" name="id" value = "{{ $tred->id }}">
									
									<label class="label_">Tred name</label>
											<input class="form-control" type = "text" name="value" value="{{ $tred->value }}" required/>
<br />
									<label class="label_">Officer recruit</label>
										<select class="form-control" name="officer_recruit">
											<option value="1" <?= $tred->officer_recruit == '1' ? 'selected': ''; ?>>Officer</option>
											<option value="2" <?= $tred->officer_recruit == '2' ? 'selected': ''; ?>>Other rank</option>
											<option value="3" <?= $tred->officer_recruit == '3' ? 'selected': ''; ?>>Both</option>
											</select>
									<div class="subBtn text-center"><br />
										<button class="btn btn-primary" type="submit" id="submit" name="submit">SAVE CHANGES</button>
										<a href="{{ url('/admin/tred') }}" class="btn btn-default"> CANCEL</a>
									</div>
								</form>
							</div>
						</div>
		<!--#### 3 div open in sidebar ####-->
				</div>	
			</div>
		</div>
		<!--#### 3 div open in sidebar ####-->
	</body>
</html>