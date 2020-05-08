@extends('frontend/layouts/app')
<div class="demo-card-wide mdl-card mdl-shadow--2dp">
    <div class="mdl-card__title">
        <h2 class="mdl-card__title-text">Create Profile</h2>
    </div>
    <div class="mdl-card__supporting-text">
        <form action="#">
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input class="mdl-textfield__input" ng-model="user.first_name" id="first_name" type="text">
                <label class="mdl-textfield__label" for="first_name">First Name</label>
            </div>
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input class="mdl-textfield__input" ng-model="user.last_name" id="last_name" type="text">
                <label class="mdl-textfield__label" for="last_name">Last Name</label>
            </div>
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input class="mdl-textfield__input" ng-model="user.email" id="email" type="text">
                <label class="mdl-textfield__label" for="email">Email</label>
            </div>
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input class="mdl-textfield__input" ng-model="user.username" id="username" type="text">
                <label class="mdl-textfield__label" for="username">Username</label>
            </div>
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input class="mdl-textfield__input" ng-model="user.password" id="password" type="password">
                <label class="mdl-textfield__label" for="password">Password</label>
            </div>
        </form>
    </div>
    <div class="mdl-card__actions mdl-card--border">
        <a class="mdl-button mdl-button--colored mdl-js-button mdl-js-ripple-effect">
            Sign Up
        </a>
    </div>
</div>