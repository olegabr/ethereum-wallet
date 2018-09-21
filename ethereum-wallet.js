if (!String.prototype.startsWith) {
  Object.defineProperty(String.prototype, 'startsWith', {
    enumerable: false,
    configurable: false,
    writable: false,
    value: function(searchString, position) {
      position = position || 0;
      return this.indexOf(searchString, position) === position;
    }
  });
}

// From https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Object/keys
if (!Object.keys) {
  Object.keys = (function() {
    'use strict';
    var hasOwnProperty = Object.prototype.hasOwnProperty,
        hasDontEnumBug = !({ toString: null }).propertyIsEnumerable('toString'),
        dontEnums = [
          'toString',
          'toLocaleString',
          'valueOf',
          'hasOwnProperty',
          'isPrototypeOf',
          'propertyIsEnumerable',
          'constructor'
        ],
        dontEnumsLength = dontEnums.length;

    return function(obj) {
      if (typeof obj !== 'object' && (typeof obj !== 'function' || obj === null)) {
        throw new TypeError('Object.keys called on non-object');
      }

      var result = [], prop, i;

      for (prop in obj) {
        if (hasOwnProperty.call(obj, prop)) {
          result.push(prop);
        }
      }

      if (hasDontEnumBug) {
        for (i = 0; i < dontEnumsLength; i++) {
          if (hasOwnProperty.call(obj, dontEnums[i])) {
            result.push(dontEnums[i]);
          }
        }
      }
      return result;
    };
  }());
}

function ETHEREUM_WALLET_copyAddress(e) {
	e.preventDefault();
	// copy in any case
	var $temp = jQuery("<input>");
	jQuery("body").append($temp);

	var id = jQuery(e.target).data("input-id");
	console.log("Copy from: ", id);

	var value = jQuery("#" + id).val();
	console.log("Value to copy: ", value);

	$temp.val(value).select();		
	document.execCommand("copy");
	$temp.remove();

    alert(window.ethereumWallet.str_copied_msg);
}

function ETHEREUM_WALLET_init() {
	if ("undefined" === typeof window['ethereumWallet']) {
        return;
    }
	if (window.ethereumWallet.initialized === true) {
        return;
    }
	if ("undefined" !== typeof window.ethereumWallet['web3Endpoint']) {
		if (typeof window !== 'undefined' && typeof window.web3 !== 'undefined') {
            var injectedProvider = window.web3.currentProvider;
            window.ethereumWallet.web3metamask = new Web3(injectedProvider)
		}
        window.ethereumWallet.web3 = new Web3(new Web3.providers.HttpProvider(window.ethereumWallet.web3Endpoint));
	}

//    jQuery(".ethereum-wallet-copy-button").click(ETHEREUM_WALLET_copyAddress);
    ETHEREUM_WALLET_update_transactions();
    
    var transactionHash = window.ethereumWallet.user_wallet_last_txhash;
    if ('' !== transactionHash) {
        var blockCount = 1; // TODO: add admin setting
        var timeout = 5 * 60; // 5 minutes in seconds
        var transactionTimeStr = window.ethereumWallet.user_wallet_last_txtime;
        // for old transactions made before this update
        // set it to expire immediately
        var transactionTime = timeout * 1000;
        if ('' !== transactionTimeStr) {
            // for new transactions count it's real time
            transactionTime = 1000 * parseInt(transactionTimeStr);
        }
        var tm = new Date;
        var startTime = new Date(tm.getTime() - transactionTime);

        if ((tm - startTime) < 1000 * timeout) {
            ETHEREUM_WALLET_show_wait_icon();
            ETHEREUM_WALLET_awaitBlockConsensus(transactionHash, blockCount, timeout, transactionTime, function(err, transaction_receipt) {
                if (err) {
                    ETHEREUM_WALLET_hide_wait_icon();
                    console.log(err);
                    return;
                }
                // wait 15 seconds for etherscan API to reflect tx changes
                setTimeout(function() {
                    ETHEREUM_WALLET_hide_wait_icon();
                    ETHEREUM_WALLET_update_transactions();
                }, 15000);
            });
        }
    }
    window.ethereumWallet.initialized = true;
}

jQuery(document).ready(ETHEREUM_WALLET_init);
// proper init if loaded by ajax
jQuery(document).ajaxComplete(function( event, xhr, settings ) {
    // check if the loaded content contains our shortcodes
    if (!xhr ||
        'undefined' === typeof xhr.responseText || 
        (
            xhr.responseText.indexOf('ethereum-wallet-account-shortcode') === -1 &&
            xhr.responseText.indexOf('ethereum-wallet-sendform-shortcode') === -1 &&
            xhr.responseText.indexOf('ethereum-wallet-balance-shortcode') === -1 &&
            xhr.responseText.indexOf('ethereum-wallet-tokens-list-shortcode') === -1 &&
            xhr.responseText.indexOf('ethereum-wallet-history-shortcode') === -1
        )
    ) {
        return;
    }
    ETHEREUM_WALLET_init();
});

function ETHEREUM_WALLET_update_transactions() {
    if (0 === jQuery('.ethereum-wallet-history-table').length) {
        return;
    }
    ETHEREUM_WALLET_load_transactions(window.ethereumWallet.user_wallet_address, function(err, transactions) {
        if (err) {
            console.log(err);
            transactions = [];
        }

        ETHEREUM_WALLET_load_internal_transactions(window.ethereumWallet.user_wallet_address, function(err, internal_transactions) {
            if (err) {
                console.log(err);
                internal_transactions = [];
            }

            var result = ETHEREUM_WALLET_prepare_transactions(transactions, window.ethereumWallet.user_wallet_address);
            var internal_result = ETHEREUM_WALLET_prepare_transactions(internal_transactions, window.ethereumWallet.user_wallet_address);
            result = result.concat(internal_result);
            // make unique
            var resultObj = {};
            result.forEach(function(r) {
                resultObj[r.hash] = r;
            });
            result = Object.keys(resultObj).map(function(key) {
                return resultObj[key];
            });
            result.sort(function(a, b) {
                // DESC
                return b.timeStamp - a.timeStamp;
            })
            ETHEREUM_WALLET_render_transactions(result);
        });
    });
}

function ETHEREUM_WALLET_render_transactions(transactions) {
    // clear tbody
    jQuery( ".ethereum-wallet-history-table tbody" ).html("");

    var blockchain_network = '';
    if ('mainnet' !== window.ethereumWallet.blockchain_network) {
        blockchain_network = window.ethereumWallet.blockchain_network + '.';
    }
    for (var i = 0; i < transactions.length; i++) {
        var t = transactions[i];
        var days = (new Date - new Date(t.timeStamp * 1000)) / (24 * 3600 * 1000);
        var dateString = '';
        if (days >= 1) {
            dateString = Math.floor(days) + " days";
        } else {
            var hours = 24 * days;
            if (hours >= 1) {
                dateString = Math.floor(hours) + " hours";
            } else {
                var minutes = 60 * hours;
                if (minutes >= 1) {
                    dateString = Math.floor(minutes) + " minutes";
                } else {
                    dateString = "recently";
                }
            }
        }
        var thash = t.hash.substr(0, 8);
        var value = t.value;

        jQuery('.ethereum-wallet-history-table').each(function() {
            $tbl = jQuery( this );
            $tbody = $tbl.find('tbody');
            if ($tbody.length === 0) {
                return;
            }
            var count = $tbody.children().length;
            if (count >= 10) {
                return;
            }
            var tr = '<tr>';
            tr += '<th scope="row">' + (count + 1) + '</th>';
            if (!t.dir) {
                tr += '<td><span class="ethereum-wallet-history-dir ethereum-wallet-history-minus">-</span>' + value + '</td>';
            } else {
                tr += '<td>' + value + '</td>';
            }
            tr += '<td>' + dateString + '</td>';
            tr += '<td><a target="_blank" href="https://' + blockchain_network + 'etherscan.io/tx/' + t.hash + '">' + thash + '</td>';
            tr += '</tr>';
            
            var inputOnly = $tbl.hasClass('ethereum-wallet-history-table-direction-in');
            var outputOnly = $tbl.hasClass('ethereum-wallet-history-table-direction-out');

            // t.dir == true means input
            if (t.dir && outputOnly) {
                return;
            }
            if (!t.dir && inputOnly) {
                return;
            }
            $tbody = $tbl.find('tbody');
            if ($tbody.length > 0 && $tbody.children().length < 10) {
                jQuery( tr ).appendTo( $tbody );
            }
        });
    }
}

function ETHEREUM_WALLET_prepare_transactions(transactions, toAddress) {
    var result = [];
    for (var i = 0; i < transactions.length; i++) {
        var t = transactions[i];
        if (t.value === '0') {
            continue;
        }
        var value = parseFloat(t.value) / 1000000000000000000;

        result.push({
            value: value + ' ETH',
            timeStamp: parseInt(t.timeStamp),
            hash: t.hash,
            // true if input
            dir: toAddress.toLowerCase() === t.to.toLowerCase(),
        });
    }
    return result;
}

function ETHEREUM_WALLET_validate_send_form() {
    var balance = window.ethereumWallet.web3.eth.getBalance(window.ethereumWallet.user_wallet_address);
    if (balance.toNumber() === 0) {
        alert(window.ethereumWallet.str_insufficient_eth_balance_msg);
        return false;
    }
}

function ETHEREUM_WALLET_load_transactions(address, callback) {
    if ('' === address) {
        callback.call(null, "Empty address requested for ETHEREUM_WALLET_load_transactions!", null);
        return;
    }
    var blockchain_network = '';
    if ('mainnet' !== window.ethereumWallet.blockchain_network) {
        blockchain_network= '-' + window.ethereumWallet.blockchain_network;
    }
    // https://stackoverflow.com/a/42538992/4256005
    jQuery.ajax({
        headers:{  
            "Accept":"application/json",//depends on your api
            "Content-type":"application/x-www-form-urlencoded"//depends on your api
        },
        url: "https://api" + blockchain_network + ".etherscan.io/api?module=account&action=txlist&address=" + address + "&startblock=0&endblock=99999999&sort=desc",
        success:function(r) {
            if (r.status !== "1") {
                console.log(r.message);
                callback.call(null, r.message, null);
                return;
            }
            var trxns = r.result;
            callback.call(null, null, trxns);
        }
    });
}

function ETHEREUM_WALLET_load_internal_transactions(address, callback) {
    if ('' === address) {
        callback.call(null, "Empty address requested for ETHEREUM_WALLET_load_internal_transactions!", null);
        return;
    }
    var blockchain_network = '';
    if ('mainnet' !== window.ethereumWallet.blockchain_network) {
        blockchain_network= '-' + window.ethereumWallet.blockchain_network;
    }
    // https://stackoverflow.com/a/42538992/4256005
    jQuery.ajax({
        headers:{  
            "Accept":"application/json",//depends on your api
            "Content-type":"application/x-www-form-urlencoded"//depends on your api
        },
        url: "https://api" + blockchain_network + ".etherscan.io/api?module=account&action=txlistinternal&address=" + address + "&startblock=0&endblock=99999999&sort=desc",
        success:function(r) {
            if (r.status !== "1") {
                console.log(r.message);
                callback.call(null, r.message, null);
                return;
            }
            var trxns = r.result;
            callback.call(null, null, trxns);
        }
    });
}

// https://ethereum.stackexchange.com/a/2830
// @method ETHEREUM_WALLET_awaitBlockConsensus
// @param txhash is the transaction hash from when you submitted the transaction
// @param blockCount is the number of blocks to wait for.
// @param timeout in seconds 
// @param callback - callback(error, transaction_receipt) 
//
function ETHEREUM_WALLET_awaitBlockConsensus(txhash, blockCount, timeout, transactionTime, callback) {
    var txWeb3 = window.ethereumWallet.web3;
    var startBlock = Number.MAX_SAFE_INTEGER;
    var stateEnum = { start: 1, mined: 2, awaited: 3, confirmed: 4, unconfirmed: 5 };
    var savedTxInfo;
    // the actual transaction start time
    var startTime = new Date((new Date).getTime() - transactionTime);

    var pollState = stateEnum.start;
    
    var checkTimeout = function() {
        var tm = new Date;
        if ((tm - startTime) > 1000 * timeout) {
            pollState = stateEnum.unconfirmed;
            callback(new Error("Timed out, not confirmed"), null);
            return false;
        }
        return true;
    };

    var processPollStateStart = function() {
        if (!checkTimeout()) {
            return;
        }
        txWeb3.eth.getTransaction(txhash, function(e, txInfo) {
            if (e || txInfo === null) {
                console.log(e);
                setTimeout(processPollStateStart, 1000);
                return; // XXX silently drop errors
            }
            if (txInfo.blockHash !== null) {
                startBlock = txInfo.blockNumber;
                savedTxInfo = txInfo;
                console.log("mined");
                pollState = stateEnum.mined;
                setTimeout(processPollStateMined, 1);
            } else {
                setTimeout(processPollStateStart, 1000);
            }
        });
    };
    var processPollStateMined = function() {
        if (!checkTimeout()) {
            return;
        }
        txWeb3.eth.getBlockNumber(function (e, blockNum) {
            if (e) {
                console.log(e);
                setTimeout(processPollStateMined, 1000);
                return; // XXX silently drop errors
            }
            console.log("blockNum: ", blockNum);
            if (blockNum >= (blockCount + startBlock)) {
                pollState = stateEnum.awaited;
                setTimeout(processPollStateAwaited, 1);
            } else {
                console.log("Need to wait ", (blockCount + startBlock - blockNum), " blocks");
                setTimeout(processPollStateMined, 1000);
            }
        });
    };
    var processPollStateAwaited = function() {
        if (!checkTimeout()) {
            return;
        }
        txWeb3.eth.getTransactionReceipt(txhash, function(e, receipt) {
            if (e || receipt === null) {
                if (e) {
                    console.log("getTransactionReceipt: ", e);
                }
                setTimeout(processPollStateAwaited, 1000);
                return; // XXX silently drop errors.  TBD callback error?
            }
            console.log("receipt: ", receipt);
            // confirm we didn't run out of gas
            // XXX this is where we should be checking a plurality of nodes.  TBD
            if (receipt.gasUsed >= savedTxInfo.gas) {
                pollState = stateEnum.unconfirmed;
                callback(new Error("we ran out of gas, not confirmed!"), null);
            } else {
                pollState = stateEnum.confirmed;
                callback(null, receipt);
            }
        });
    };

    processPollStateStart();
}

function ETHEREUM_WALLET_show_wait_icon() {
    jQuery('#ethereum-wallet-tx-in-progress-spinner').addClass('is-active');
    jQuery('#ethereum-wallet-tx-in-progress-alert').removeClass('hidden');
    jQuery('#ethereum-wallet-tx-in-progress-alert').removeAttr('hidden');
}

function ETHEREUM_WALLET_hide_wait_icon() {
    jQuery('#ethereum-wallet-tx-in-progress-spinner').removeClass('is-active');
    jQuery('#ethereum-wallet-tx-in-progress-alert').addClass('hidden');
    jQuery('#ethereum-wallet-tx-in-progress-alert').attr('hidden', ' hidden');
}
