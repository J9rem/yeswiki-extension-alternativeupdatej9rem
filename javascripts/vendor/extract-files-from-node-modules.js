/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// Extract files that we need from the node_modules folder
// The extracted files are integrated to the repository, so production server don't need to
// have node installed

// Include fs and path module

const fs = require('fs-extra')
const path = require('path');
const basePath = path.join(__dirname, '../../');

function copySync(src,dest,opts){
  if (fs.existsSync( src )){
    fs.copySync(path.join(basePath,src),path.join(basePath,dest),opts);
  } else {
    console.log(src+" is not existing !");
  }
}

function mergeFilesSync(sources,dest){
  
  var fullDest = path.join(basePath,dest);
  if (!fs.existsSync( fullDest )){
    fs.mkdirSync(path.dirname(fullDest),{recursive:true});
  } else {
    fs.unlinkSync(fullDest);
  }
  sources.forEach( file => {
    var fullSrc = path.join(basePath,file);
    if (!fs.existsSync( fullSrc )){
      console.log(fullSrc+" is not existing !");
    } else {
      fs.appendFileSync(fullDest,fs.readFileSync(fullSrc));
    }
  });
}

// jszip
copySync('node_modules/jszip/dist/jszip.min.js','javascripts/vendor/jszip/jszip.min.js',{overwrite:true});
// license files
copySync('node_modules/jszip/','javascripts/vendor/jszip/',{overwrite:true,
  filter: function (src,dest) {
    return (fs.statSync(src).isDirectory() && path.basename(src) == "jszip") || ["LICENSE.markdown",'package.json','README.markdown'].includes(path.basename(src));
  }});

// jszip-utils
copySync('node_modules/jszip-utils/dist/jszip-utils.min.js','javascripts/vendor/jszip-utils/jszip-utils.min.js',{overwrite:true});
// license files
copySync('node_modules/jszip-utils/','javascripts/vendor/jszip-utils/',{overwrite:true,
  filter: function (src,dest) {
    return (fs.statSync(src).isDirectory() && path.basename(src) == "jszip-utils") || ["LICENSE.markdown",'package.json','README.markdown'].includes(path.basename(src));
  }});

// example
// mergeFilesSync(
//   [
//     'node_modules/file1.js',
//     'node_modules/file2.js',
//   ],
//   'javascripts/vendor/output-file.js'); 
