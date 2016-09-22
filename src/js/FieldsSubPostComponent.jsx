import React from 'react';
import InputComponent from './InputComponent.jsx';
import TacoStr from './util/TacoStr.js';
import TacoGeneral from './util/TacoGeneral.js';
import { Provider } from 'react-redux'

export default class FieldsSubPostComponent extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      fields: null
    };
  }

  componentDidMount() {
    this.checkAndAddWYSIWYG();
    this.checkAndAddFileUploads();
  }

  getRendered() {
    let self = this;
    let group = [];
    this.selectorsWithWysiwyg = [];
    this.selectorsWithFile = [];
    const { store } = this.context;
    const state  = store.getState();

    if(this.props.fields == null) {
      return;
    }

    for(o in this.props.fields) {
      let fieldName = 'subposts[' + state.fieldName + '][' + this.props.subpostId + '][' + o + ']';
      let props = Object.assign({}, this.props.fields[o]);
      let fieldAttribs = props.attribs;

      if(typeof fieldAttribs.class != 'undefined' && fieldAttribs.class == 'wysiwyg') {
        this.selectorsWithWysiwyg.push(fieldName);
      }
      if(fieldAttribs.type === 'image' || fieldAttribs.type === 'file') {
        this.selectorsWithFile.push(fieldName);
      }
      if(typeof fieldAttribs.id === 'undefined' || fieldAttribs.type === 'image' || fieldAttribs.type === 'file') {
        fieldAttribs.id = TacoStr.human(fieldName).replace(/[^a-z\-0-9\_]+/ig, '-').toLowerCase();
      }
      if(typeof fieldAttribs.class !== 'undefined') {
        fieldAttribs.className = fieldAttribs.class;
        delete fieldAttribs.class;
      }

      group.push(
        <tr key={o}>
          <td>{TacoStr.human(o)}</td>
          <td>
            <InputComponent
              name={fieldName}
              dbValue={props.value}
              attribs={fieldAttribs}
            />
          </td>
        </tr>
      );
    }
    return group;
  }

  render() {
    const props = this.props;
    const { store } = this.context;
    const state  = store.getState();
    let renderedFields = this.getRendered();
    let orderfieldName = 'subposts[' + state.fieldName + '][' + this.props.subpostId + '][order]';

    let styles = { display: 'none' };
    let addBySearchContent = null;

    if(this.props.postReferenceInfo != null) {
      let postReferenceInfo = this.props.postReferenceInfo;
      let postReferencefieldName = 'subposts[' + state.fieldName + '][' + this.props.subpostId + '][post_reference_id]';
      let noFieldsClass = '';
      if(!Object.keys(this.props.fields).length) {
        noFieldsClass = 'no-fields';
      }
      addBySearchContent = (
        <tr>
          <td colSpan="2" className={'addbysearch-reference-td ' + noFieldsClass}>

            <a
              className="addmany-edit-link"
              href={'/wp-admin/post.php?post=' + postReferenceInfo.postId+ '&action=edit'}
              target="_blank">

              <span>{postReferenceInfo.postTitle}</span>
              <span className="dashicons dashicons-external"></span>
            </a>

            <InputComponent
              attribs={{type: 'hidden'}}
              name={postReferencefieldName}
              dbValue={postReferenceInfo.postId} />
          </td>
        </tr>
      );
    }
    return (
      <tbody>

        {
          (this.props.isAddBySearch)
          ? addBySearchContent
          : <tr>
              <td
                colSpan="2"
                className="no-addbysearch-td">
              </td>
            </tr>
        }

        <tr style={styles}>

          <td>
            <InputComponent
              attribs={{type: 'hidden'}}
              name={orderfieldName}
              dbValue={this.props.order} />
          </td>

          <td>
            <InputComponent
              attribs={{type: 'hidden'}}
              name="parent_field_name"
              dbValue={state.fieldName} />
          </td>

        </tr>
        {renderedFields}
      </tbody>
    );
  }

  checkAndAddWYSIWYG() {
    // WYSIWYG editors
    let $ = jQuery;
    let self = this;

    this.selectorsWithWysiwyg.forEach(function(s){
      if(typeof(tinyMCE) == 'object' && typeof( tinyMCE.execCommand ) == 'function') {
        tinyMCE.execCommand('mceAddEditor', true, $('[name="' + s + '"]').attr('id'));
      }
    });
  }

  checkAndAddFileUploads() {
    // Initial loading of thumbnail
    let $ = jQuery;
    let self = this;

    this.selectorsWithFile.forEach(function(s){
      let $obj = $('[name="' + s + '"]');
      if($obj.length) {
        if($obj.val().match(/(jpg|jpeg|png|gif)$/)) {
          $obj.addImage($obj.val());
        }
      }
    });
  }

  getPreviewThumb($obj) {
    if($obj.val().search(/jpg|jpeg|png|gif/gi) > -1) {
      $obj.addImage($obj.val());
    }
  }
}

FieldsSubPostComponent.contextTypes = { store: React.PropTypes.object };
FieldsSubPostComponent.defaultProps = {};
