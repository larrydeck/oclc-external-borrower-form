@extends('layouts.mainlayout')

@section('content')
    <h1>Library Application Form - Review</h1>
    <hr>
    <h3>Review Your Details</h3>
    <form action="store" method="post" >
        {{ csrf_field() }}
        <table class="table">
            <tr>
                <td>First name:</td>
                <td><strong>{{$borrower->fname}}</strong></td>
            </tr>
            <tr>
                <td>Last name:</td>
                <td><strong>{{$borrower->lname}}</strong></td>
            </tr>
            <tr>
                <td>Email address:</td>
                <td><strong>{{$borrower->email}}</strong></td>
            </tr>
	    @if (isset($borrower->telephone_no))
            <tr>
                <td>Telephone:</td>
		<td><strong>{{$borrower->telephone_no}}</strong></td>
	    </tr>
	    @endif
            <tr>
                <td>Requested Borrowing Category:</td>
                <td><strong>{{$borrower->getBorrowerCategoryName($borrower->borrower_cat)}}</strong></td>
	    </tr>
	    @if (isset($borrower->spouse_name))
		    <tr>
			<td>Spouse's name:</td>
			<td><strong>{{$borrower->spouse_name}}</strong></td>
		    </tr>
	    @endif
	    @if (isset($borrower->home_institution))
		    <tr>
			<td>Home institution name:</td>
			<td><strong>{{$borrower->home_institution}}</strong></td>
		    </tr>
	    @endif
	    @if (isset($borrower->postal_code))
            <tr>
                <td>Address:</td>
		<td><strong>
			<address>
			{{$borrower->address1}}
			{{$borrower->address2}}<br />
			{{$borrower->city}}<br />
			{{$borrower->postal_code}}<br/>
			</address>
	            </strong>
		</td>
	    </tr>
	    @endif
        </table>
        <div class="form-group row">
		<label class="col-lg-3 col-form-label form-control-label"></label>
		<div class="col-lg-9">
        		<a type="button" href="create-step1" class="btn btn-primary">Back</a>
			<input class="btn btn-secondary" type="submit" value="Submit application">
		</div>
    	</div>
    </form>
@endsection
