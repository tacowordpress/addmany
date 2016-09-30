import React from 'react';
import ReactDOM from 'react-dom';
import SubPostComponent from './SubPostComponent.jsx';
import InputComponent from './InputComponent.jsx';
import TacoStr from './util/TacoStr.js';
import { Provider } from 'react-redux'

export default class AddManyComponent extends React.Component {

  componentDidMount(){
    const { store } = this.context;
    this.loadSaved();
    store.dispatch({
      type: 'INIT',
      currentVariation: this.getDefaultVariation(),
      fieldName: this.props.fieldName,
      removedSubpostIds: [],
      searchButtonText: 'Show all',
    });
    this.addPostSaveHook();
  }

  addPostSaveHook() {
    let $ = jQuery;
    let self = this;
    $('#post').on('submit', function(e){
      if(!self.limitCheckMin()) {
        return false;
      }
      return true;
    });
  }

  getDefaultVariation() {
    let field_variations = this.props.fieldDefinitions[this.props.fieldName].field_variations;
    return (!this.props.isAddBySearch)
      ?(Object.keys(field_variations)[0])
      : 'default_variation'
  }


  render() {
    const { store } = this.context;
    const {
      subposts,
      removedSubpostIds,
      searchButtonText,
      loadingClass,
      resultsMessage,
      currentVariation
    } = store.getState();

    let variations = this.getFieldsVariationOptions();
    let removed = (removedSubpostIds === null || typeof removedSubpostIds === 'undefined' )
      ? []
      : removedSubpostIds;

    let renderedSubposts = [];
    if(typeof subposts != 'undefined') {
      subposts.forEach((s) => {
       renderedSubposts.push(
         <SubPostComponent
           key={s.postId}
           postId={s.postId}
           fieldsConfig={s.fieldsConfig}
           uniqid={s.postId}
           isAddBySearch={s.isAddBySearch}
           postReferenceInfo={s.postReferenceInfo}
           parentComponent={this} />
         );
     });
    }

    if(!this.props.isAddBySearch) {
      return (
        <div ref="main_container" className="addmany-component">

          <input name="addmany_deleted_ids" type="hidden" value={removed} />

          {
            (variations !== null)
              ? <select
                  value={currentVariation}
                  onChange={(e) => {
                    store.dispatch({
                      type: 'UPDATE_VARIATION',
                      variation: e.target.value
                    })
                  }}>
                  {this.getFieldsVariationOptionsHtml()}
                </select>
              : null
          }

          <button
            className="button"
            onClick={this.createNewSubPost.bind(this)}>Add new</button>

          <ul className="addmany-actual-values">{renderedSubposts}</ul>

        </div>
      );
    } else {
      return (
        <div ref="main_container" className="addmany-component with-addbysearch">

          <input
            name="addmany_deleted_ids"
            type="hidden"
            value={removed}
          />

          <input
            type="text"
            ref="searchableText"
            placeholder="search for posts"
            className={'addmany-searchable-field ' + loadingClass }
            onKeyPress={this.handleKeywordChange.bind(this)}
            onChange={this.handleKeywordChange.bind(this)} />

          <button
            className="button"
            onClick={this.searchPosts.bind(this)}>
            {searchButtonText}
          </button>

          <br />
          <br />

          <b>Search Results</b><em style={{float: 'right'}}>{resultsMessage}</em>
          <ul className="addmany-search-results">{this.renderSearchResults()}</ul>

          <br />
          <br />

          <b>Your Selection</b>
          <ul className="addmany-sorting-buttons">
            <li><button className="button" onClick={this.sortPostsReverse.bind(this)}>Reverse</button></li>
            <li><button className="button" onClick={this.sortPostsAlpha.bind(this)}>Alpha</button></li>
            <li><button className="button" onClick={this.sortPostsDate.bind(this)}>Post Date</button></li>
          </ul>
          <ul className="addmany-actual-values">{renderedSubposts}</ul>

        </div>
      );
    }
  }

  renderSearchResults() {
    const { store } = this.context;
    const state = store.getState();
    if(typeof state.searchResultPosts == 'undefined') {
      return null;
    }
    let rendered = [];
    state.searchResultPosts.forEach((p) => {
      rendered.push(
        <li
          data-post-id={p.postId}
          key={p.postId}
          className="addbysearch-result postbox">
            <a
              href={'/wp-admin/post.php?post=' + p.postId + '&action=edit'}
              target="_blank">
              <span>{p.postTitle}</span>
              <span  className="dashicons dashicons-external"></span>
            </a>

            <button
              title="Click to add this post to the selection below."
              className="button"
              style={{float: 'right'}}
              onClick={this.cloneResultIntoSelection.bind({
                context: this,
                postReferenceInfo: {
                  postId: p.postId,
                  postTitle: p.postTitle
                }
              })}>
              <span className="dashicons dashicons-plus-alt"></span>
            </button>
        </li>
      );
    });
    return rendered;
  }

  handleKeywordChange(e){
    const { store } = this.context;
    store.dispatch({
      type: 'SET_KEYWORDS',
      keywords: e.target.value,
      searchButtonText: (e.target.value) ? 'Search' : 'Show all'
    });
    if(e.which === 13) { /* Enter */
      e.preventDefault();
      this.searchPosts(e);
    }
  }

  searchPosts(e) {
    e.preventDefault();
    var $ = jQuery;
    var self = this;
    const { store } = this.context;
    const state = store.getState();
    store.dispatch({
      type: 'SET_LOADING_STATE',
      loadingClass: 'is-loading'
    });
    $.ajax({
      url: AJAXSubmit.ajaxurl,
      method: 'post',
      data: {
        is_addbysearch: true,
        class_method: this.props.classMethod,
        field_assigned_to: this.props.fieldName,
        parent_id: this.props.parentPostId,
        keywords: state.keywords,
        action: 'AJAXSubmit',
        AJAXSubmit_nonce : AJAXSubmit.AJAXSubmit_nonce
      }
    }).success(function(d) {
      if(d.success) {
        return self.addSearchResults(d.posts);
      }
      self.clearResults();
      store.dispatch({
        type: 'SET_LOADING_STATE',
        loadingClass: '',
        resultsMessage: 'The search returned zero results.'
      });
    });
  }

  clearResults() {
    const { store } = this.context;
    const state = store.getState();
    store.dispatch({
      type: 'UPDATE_SEARCH_RESULTS',
      searchResultPosts: []
    });
  }

  addSearchResults(posts) {
    const { store } = this.context;
    const state = store.getState();
    let searchResultPosts = [];

    posts.forEach((p) => {
      searchResultPosts.push(
        {
          postId: p.postId,
          postTitle: p.postTitle
        }
      );
    });

    store.dispatch({
      type: 'UPDATE_SEARCH_RESULTS',
      searchResultPosts: searchResultPosts,
      loadingClass: '',
      resultsMessage: searchResultPosts.length + ' results'
    });
  }

  cloneResultIntoSelection(e) {
    e.preventDefault();
    let context = this.context;
    context.createNewSubPost(e, this.postReferenceInfo);
  }

  getFieldsVariationOptionsHtml() {
    let variationOptions = this.getFieldsVariationOptions();
    let htmlVariationOptions = [];

    Object.keys(variationOptions).forEach((key) => {
      htmlVariationOptions.push(
        <option key={key} value={key}>{variationOptions[key]}</option>
      );
    });
    return htmlVariationOptions;
  }

  addRow(postId, fieldsConfig = null, allData = null) {
    const { store } = this.context;
    const state = store.getState();
    let subposts = state.subposts.slice(0);
    let postReferenceInfo = {};

    if(allData != null) {
      if(typeof allData.postReferenceInfo !== 'undefined') {
        postReferenceInfo = allData.postReferenceInfo;
      }
    }
    let subpost = {
      postId: postId,
      fieldsConfig: fieldsConfig,
      isAddBySearch: this.props.isAddBySearch,
      postReferenceInfo: postReferenceInfo
    }

    subposts.push(subpost);

    store.dispatch({
      type: 'ADD_SUBPOST',
      subposts: subposts
    });
  }

  addRows(loadedSubposts) {
    const { store } = this.context;
    const state = store.getState();
    let subposts = state.subposts.slice(0);
    let self = this;
    loadedSubposts.forEach(function(s) {
      self.addRow(s.postId, s.fields, s);
    });
  }

  componentWillMount(){
    const { store } = this.context;
    let subfields = this.props.fieldDefinitions[this.props.fieldName];
    store.dispatch({
      type: 'UPDATE_VARIATION',
      variation: Object.keys(subfields)[0]
    });
  }

  getFieldsVariationOptions(){
    let subfields = this.props.fieldDefinitions[this.props.fieldName].field_variations;
    if(Object.keys(subfields).length === 1) {
      return null;
    }
    let options = {};
    Object.keys(subfields).forEach(function(k) {
      options[k] = TacoStr.human(k);
    });
    return options;
  }

  forceUpdateOrder() {
    let self = this;
    const { store } = this.context;
    const state = store.getState();
    let subposts = state.subposts;
    let $ = jQuery;
    let newArrayOfSubposts = [];

    $('tr.' + this.props.fieldName + ' ul').find('li').each(function(i) {
      let $this = $(this);
      subposts.forEach(function(s) {
        if(s.postId === $this.data('subpostId')) {
          newArrayOfSubposts.push(Object.assign({}, s, { order: i }));
        }
      });
    });
    store.dispatch({
      type: 'UPDATE_ORDERING',
      subposts: newArrayOfSubposts
    });
  }

  limitCheckMax() {
    let self = this;
    const { store } = this.context;
    const state = store.getState();
    const subposts = state.subposts;
    if(!this.props.limitRange.length) {
      return false;
    }
    if(subposts.length === this.props.limitRange[1]) {
      alert('Item not added. You have reached the max number of items.')
      return true;
    }
  }

  limitCheckMin() {
    let self = this;
    const { store } = this.context;
    const state = store.getState();
    const subposts = state.subposts;

    if(!this.props.limitRange.length) {
      return true;
    }
    if(subposts.length < this.props.limitRange[0]) {
      alert('You must have at least ' + this.props.limitRange[0] + ' items selected for the "' + TacoStr.human(this.props.fieldName) + '" field.' );
      return false;
    }
    return true;
  }

  createNewSubPost(e, postReferenceInfo=null) {
    e.preventDefault();
    if(this.limitCheckMax()) {
      return;
    }
    let $ = jQuery;
    let self = this;
    const { store } = this.context;

    $.ajax({
      url: this.props.submitURL,
      method: 'post',
      data: {
        parent_id: this.props.parentPostId,
        action: 'AJAXSubmit',
        AJAXSubmit_nonce: AJAXSubmit.AJAXSubmit_nonce,
        field_assigned_to: this.props.fieldName,
        current_variation: store.getState().currentVariation,
        post_reference_id: (postReferenceInfo != null)
          ? postReferenceInfo.postId
          : null
      }
    }).success(function(d) {
      if(d.success) {
        self.addRow(d.posts[0].postId, d.posts[0].fields, d)
      }
    });
  }

  getOrder(postId) {
    const { store } = this.context;
    let subposts = store.getState().subposts;
    let self = this;
    let order = 0;
    subposts.forEach(function(subpost, index){
      if(postId === subpost.postId) {
        order = index;
        return;
      }
    });
    return order;
  }

  sortPostsReverse(e) {
    e.preventDefault();
    const { store } = this.context;
    let subposts = store.getState().subposts.slice(0).reverse();
    store.dispatch({
      type: 'UPDATE_ORDERING',
      subposts: subposts
    });
  }

  sortPostsAlpha(e) {
    e.preventDefault();
    const { store } = this.context;
    let subposts = store.getState().subposts.slice(0).reverse();
    let subpostTitles = subposts.map((s) => {
      return s.postReferenceInfo.postTitle.toLowerCase();
    });

    subpostTitles.sort();
    let sorted = [];
    subpostTitles.forEach((title) => {
      subposts.forEach((s) => {
        if(title === s.postReferenceInfo.postTitle.toLowerCase()) {
          sorted.push(s);
        }
      })
    })

    store.dispatch({
      type: 'UPDATE_ORDERING',
      subposts: sorted
    });
  }

  sortPostsDate(e) {
    e.preventDefault();
    const { store } = this.context;
    let subposts = store.getState().subposts.slice(0).reverse();

    let subpostDates = subposts.map((s) => {
      return s.postReferenceInfo.postDate;
    });

    subpostDates.sort();
    let sorted = [];
    subpostDates.forEach((title) => {
      subposts.forEach((s) => {
        if(title === s.postReferenceInfo.postDate.toLowerCase()) {
          sorted.push(s);
        }
      })
    })

    store.dispatch({
      type: 'UPDATE_ORDERING',
      subposts: sorted
    });
  }

  loadSaved(callback) {
    var $ = jQuery;
    var self = this;
    $.ajax({
      url: AJAXSubmit.ajaxurl,
      method: 'post',
      data: {
        get_by: true,
        field_assigned_to: this.props.fieldName,
        parent_id: this.props.parentPostId,
        action: 'AJAXSubmit',
        AJAXSubmit_nonce : AJAXSubmit.AJAXSubmit_nonce
      }
    }).success(function(d) {
      if(d.success) {
        if(d.success) {
          self.addRows(d.posts);
        }
      }
    });
  }
}
AddManyComponent.contextTypes = { store: React.PropTypes.object };
AddManyComponent.defaultProps = {};
