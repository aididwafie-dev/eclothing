<br />
<form autocomplete="off" method="post" action="{{ url('/uniform-details-save-accessories') }}" name="uniform-details" id="uniform-details">
    <input type = "hidden" name = "_token" value = "<?php echo csrf_token(); ?>">
    
    <div class="form-group">
    @foreach($accessories as $accessory)
<div class="form-check">
  <input class="form-check-input" type="checkbox" value="{{$accessory->id}}" id="accessory_{{$accessory->id}}">
  <label class="form-check-label" for="accessory_{{$accessory->id}}">
    {{$accessory->value}}
  </label>
</div>
    @endforeach
    <div class="subBtn text-center">
        <input class="btn btn-default" type="submit" value="Order" id="submit" name="submit"/> 
        <a class="btn btn-default" href="{{ url('/cancel') }}">CANCEL</a>
    </div>
</form>
<script type="text/javascript">
    $("#uniform-details").validate();
</script>